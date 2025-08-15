<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'];
$week_start = $data['week_start'];
$client_name = $data['client_name'];
$assigned_hours = $data['assigned_hours'];

// Insert entry
$stmt = $db->prepare("INSERT INTO entries (user_id, week_start, client_name, assigned_hours) VALUES (?, ?, ?, ?)");
$stmt->execute([$user_id, $week_start, $client_name, $assigned_hours]);
$entry_id = $db->lastInsertId();

// Get status from clients table
$stmt2 = $db->prepare("SELECT status FROM clients WHERE client_name = ? LIMIT 1");
$stmt2->execute([$client_name]);
$status = $stmt2->fetchColumn() ?: 'confirmed';

echo json_encode([
    'success' => true,
    'entry_id' => $entry_id,
    'status' => $status
]);
