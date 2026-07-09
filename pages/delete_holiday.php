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
$name = $data['name'];

$stmt = $conn->prepare("DELETE FROM time_off WHERE timeoff_note = ? AND is_global_timeoff = 1");
$stmt->bind_param('s', $name);
$stmt->execute();

echo json_encode(['success' => true]);
?>