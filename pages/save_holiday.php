<?php
require_once '../includes/db.php';
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'];
$days = $data['days']; // array of {date, hours}

foreach ($days as $day) {
    $date = $day['date'];
    $hours = intval($day['hours']);
    $stmt = $conn->prepare("INSERT INTO time_off (timeoff_note, week_start, assigned_hours, is_global_timeoff) VALUES (?, ?, ?, 1)");
    $stmt->bind_param('ssi', $name, $date, $hours);
    $stmt->execute();
}

echo json_encode(['success' => true]);
?>