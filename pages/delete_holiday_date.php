<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json');

$userRole = strtolower($_SESSION['user_role'] ?? '');
if ($userRole !== 'admin' && $userRole !== 'manager') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$timeoff_id = intval($data['id'] ?? 0);

if (!$timeoff_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid date ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM time_off WHERE timeoff_id = ? AND is_global_timeoff = 1");
$stmt->bind_param('i', $timeoff_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
