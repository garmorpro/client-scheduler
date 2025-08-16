<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$entry_id = $data['entry_id'] ?? null;
$field = $data['field'] ?? null;
$value = $data['value'] ?? null;

$allowedFields = ['timeoff_note', 'week_start', 'assigned_hours'];

if (!$entry_id || !$field || !in_array($field, $allowedFields)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$entry_id = $conn->real_escape_string($entry_id);
$field = $conn->real_escape_string($field);
$value = $conn->real_escape_string($value);

$sql = "UPDATE time_off SET $field = '$value' WHERE timeoff_id = '$timeoff_id' AND is_global_timeoff = 1 LIMIT 1";

if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
