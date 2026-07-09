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
$date = trim($data['date'] ?? '');
$hours = $data['hours'] ?? '';
$reason = trim($data['reason'] ?? '');

if (!$date || !is_numeric($hours) || (float)$hours <= 0) {
    echo json_encode(['success' => false, 'error' => 'A date and a positive number of hours are required.']);
    exit;
}

$parsedDate = DateTime::createFromFormat('Y-m-d', $date);
if (!$parsedDate) {
    echo json_encode(['success' => false, 'error' => 'Invalid date.']);
    exit;
}

$userId = $_SESSION['user_id'];
$weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));

$stmt = $conn->prepare("
    INSERT INTO time_off (user_id, timeoff_note, week_start, holiday_date, assigned_hours, is_global_timeoff, status)
    VALUES (?, ?, ?, ?, ?, 0, 'pending')
");
$stmt->bind_param('isssd', $userId, $reason, $weekStart, $date, $hours);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'timeoff_id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
