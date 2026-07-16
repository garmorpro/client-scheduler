<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_master_schedule')) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$timeoff_id = isset($data['timeoff_id']) ? (int)$data['timeoff_id'] : 0;
$assigned_hours = $data['assigned_hours'] ?? '';

if (!$timeoff_id || $assigned_hours === '' || !is_numeric($assigned_hours)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$assigned_hours = (float)$assigned_hours;

$stmt = $conn->prepare("UPDATE time_off SET assigned_hours = ? WHERE timeoff_id = ?");
$stmt->bind_param('di', $assigned_hours, $timeoff_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
