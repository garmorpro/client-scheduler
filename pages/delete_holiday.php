<?php
require_once '../includes/db.php';
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'];

$stmt = $conn->prepare("DELETE FROM time_off WHERE timeoff_note = ? AND is_global_timeoff = 1");
$stmt->bind_param('s', $name);
$stmt->execute();

echo json_encode(['success' => true]);
?>