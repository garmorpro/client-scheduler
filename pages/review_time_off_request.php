<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json');

$userRole = strtolower($_SESSION['user_role'] ?? '');
if (!isset($_SESSION['user_id']) || ($userRole !== 'admin' && $userRole !== 'manager')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$requestGroup = trim($data['request_group'] ?? '');
$action = $data['action'] ?? '';
$comment = trim($data['comment'] ?? '');

if (!$requestGroup || !in_array($action, ['approve', 'deny', 'request_changes'], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

if ($action === 'request_changes' && $comment === '') {
    echo json_encode(['success' => false, 'error' => 'Please add a comment explaining what needs to change.']);
    exit;
}

$statusMap = ['approve' => 'approved', 'deny' => 'denied', 'request_changes' => 'changes_requested'];
$status = $statusMap[$action];
$reviewerId = $_SESSION['user_id'];

// Managers may only review requests from staff/seniors assigned to them; admins can review anyone.
$managerScope = $userRole === 'manager' ? " AND user_id IN (SELECT user_id FROM users WHERE manager_id = ?)" : '';

if (strpos($requestGroup, 'single-') === 0) {
    $timeoffId = intval(substr($requestGroup, 7));
    $stmt = $conn->prepare("
        UPDATE time_off SET status = ?, reviewer_comment = ?, reviewed_by = ?, reviewed_at = NOW()
        WHERE timeoff_id = ? AND is_global_timeoff = 0" . $managerScope . "
    ");
    if ($userRole === 'manager') {
        $stmt->bind_param('ssiii', $status, $comment, $reviewerId, $timeoffId, $reviewerId);
    } else {
        $stmt->bind_param('ssii', $status, $comment, $reviewerId, $timeoffId);
    }
} else {
    $stmt = $conn->prepare("
        UPDATE time_off SET status = ?, reviewer_comment = ?, reviewed_by = ?, reviewed_at = NOW()
        WHERE request_group = ? AND is_global_timeoff = 0" . $managerScope . "
    ");
    if ($userRole === 'manager') {
        $stmt->bind_param('ssisi', $status, $comment, $reviewerId, $requestGroup, $reviewerId);
    } else {
        $stmt->bind_param('ssis', $status, $comment, $reviewerId, $requestGroup);
    }
}

if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

if ($stmt->affected_rows === 0) {
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'Request not found or you are not authorized to review it.']);
    exit;
}
$stmt->close();

// Keep a permanent, attributed record of this note so multiple review
// rounds (e.g. two "Request Changes" cycles) don't overwrite each other.
if ($comment !== '') {
    $logStmt = $conn->prepare("
        INSERT INTO time_off_comments (request_group, user_id, comment)
        VALUES (?, ?, ?)
    ");
    $logStmt->bind_param('sis', $requestGroup, $reviewerId, $comment);
    $logStmt->execute();
    $logStmt->close();
}

echo json_encode(['success' => true]);
$conn->close();
