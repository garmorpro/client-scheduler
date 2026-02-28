<?php
require_once '../includes/db.php';
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id']);

$stmt = $conn->prepare("DELETE FROM time_off WHERE timeoff_id = ? AND is_global_timeoff = 1");
$stmt->bind_param('i', $id);
$stmt->execute();

echo json_encode(['success' => true]);
?>