<?php
require_once '../includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id']);
$name = $data['name'];
$date = $data['date'];
$hours = intval($data['hours']);

$stmt = $conn->prepare("UPDATE time_off SET timeoff_note = ?, week_start = ?, assigned_hours = ? WHERE timeoff_id = ? AND is_global_timeoff = 1");
$stmt->bind_param('ssii', $name, $date, $hours, $id);
$stmt->execute();

echo json_encode(['success' => true]);
?>