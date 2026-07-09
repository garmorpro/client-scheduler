<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
header('Content-Type: application/json');

$userRole = strtolower($_SESSION['user_role'] ?? '');
if (!isset($_SESSION['user_id']) || ($userRole !== 'admin' && $userRole !== 'manager')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'];
$days = $data['days']; // array of {date, hours}

foreach ($days as $day) {
    $actualDate = $day['date'];
    $hours = intval($day['hours']);
    $monday = date('Y-m-d', strtotime('monday this week', strtotime($actualDate)));
    
    $stmt = $conn->prepare("INSERT INTO time_off (timeoff_note, week_start, holiday_date, assigned_hours, is_global_timeoff) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param('sssi', $name, $monday, $actualDate, $hours);
    $stmt->execute();
}

echo json_encode(['success' => true]);
?>