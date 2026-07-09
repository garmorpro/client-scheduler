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
$week_start = $data['week_start'] ?? '';

if (!$week_start) {
    echo json_encode(['success' => false]);
    exit();
}

$stmt = $conn->prepare("SELECT assigned_hours FROM time_off WHERE is_global_timeoff = 1 AND week_start = ? LIMIT 1");
$stmt->bind_param('s', $week_start);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'assigned_hours' => $row['assigned_hours']]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
