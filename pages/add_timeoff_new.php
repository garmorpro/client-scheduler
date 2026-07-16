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
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$week_start = $data['week_start'] ?? null;
$assigned_hours = $data['assigned_hours'] ?? '';

if (!$user_id || !$week_start || $assigned_hours === '' || !is_numeric($assigned_hours)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$assigned_hours = (float)$assigned_hours;

// Direct admin/manager-added time off, same as every other insert path in the
// app: explicitly individual (not a company holiday) and pre-approved, since
// it was entered directly rather than requested by the employee.
$stmt = $conn->prepare("
    INSERT INTO time_off (user_id, week_start, assigned_hours, is_global_timeoff, status)
    VALUES (?, ?, ?, 0, 'approved')
");
$stmt->bind_param('isd', $user_id, $week_start, $assigned_hours);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'timeoff_id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
