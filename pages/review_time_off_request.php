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
$timeoffId = intval($data['timeoff_id'] ?? 0);
$action = $data['action'] ?? '';

if (!$timeoffId || !in_array($action, ['approve', 'deny'], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$status = $action === 'approve' ? 'approved' : 'denied';
$reviewerId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    UPDATE time_off
    SET status = ?, reviewed_by = ?, reviewed_at = NOW()
    WHERE timeoff_id = ? AND is_global_timeoff = 0
");
$stmt->bind_param('sii', $status, $reviewerId, $timeoffId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
