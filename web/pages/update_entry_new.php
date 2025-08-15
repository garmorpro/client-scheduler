<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['entry_id'], $data['client_name'], $data['assigned_hours'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit();
}

$entry_id = intval($data['entry_id']);
$client_name = trim($data['client_name']);
$hours = floatval($data['assigned_hours']);

$stmt = $conn->prepare("UPDATE schedule_entries SET client_name = ?, assigned_hours = ? WHERE entry_id = ?");
$stmt->bind_param("sdi", $client_name, $hours, $entry_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database update failed']);
}
