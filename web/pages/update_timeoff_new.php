<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['entry_id'], $data['timeoff_note'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$entry_id = intval($data['entry_id']);
$timeoff_note = $data['timeoff_note'];

try {
    $stmt = $db->prepare("UPDATE entries SET assigned_hours = ? WHERE entry_id = ? AND is_timeoff = 1");
    $stmt->execute([$timeoff_note, $entry_id]);

    // Log for debugging
    error_log("Updated time off: entry_id=$entry_id, assigned_hours=$timeoff_note");

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Error updating time off: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
