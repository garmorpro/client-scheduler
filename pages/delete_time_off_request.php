<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
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
if (!$requestGroup) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$userRole = strtolower($_SESSION['user_role'] ?? '');
$isReviewer = $userRole === 'admin' || $userRole === 'manager';
$currentUserId = $_SESSION['user_id'];

$isSingle = strpos($requestGroup, 'single-') === 0;
$timeoffId = $isSingle ? intval(substr($requestGroup, 7)) : 0;

if ($isReviewer) {
    if ($isSingle) {
        $stmt = $conn->prepare("DELETE FROM time_off WHERE timeoff_id = ? AND is_global_timeoff = 0");
        $stmt->bind_param('i', $timeoffId);
    } else {
        $stmt = $conn->prepare("DELETE FROM time_off WHERE request_group = ? AND is_global_timeoff = 0");
        $stmt->bind_param('s', $requestGroup);
    }
} else {
    // Non-reviewers can only withdraw their own request while it's still pending.
    if ($isSingle) {
        $stmt = $conn->prepare("DELETE FROM time_off WHERE timeoff_id = ? AND is_global_timeoff = 0 AND user_id = ? AND status = 'pending'");
        $stmt->bind_param('ii', $timeoffId, $currentUserId);
    } else {
        $stmt = $conn->prepare("DELETE FROM time_off WHERE request_group = ? AND is_global_timeoff = 0 AND user_id = ? AND status = 'pending'");
        $stmt->bind_param('si', $requestGroup, $currentUserId);
    }
}

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Request not found or cannot be removed.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
