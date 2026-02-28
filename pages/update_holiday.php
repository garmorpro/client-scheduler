<?php
require_once '../includes/db.php';
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$originalName = $data['originalName'];
$newName = $data['newName'];
$updatedDays = $data['updatedDays'];
$newDays = $data['newDays'];
$deletedIds = $data['deletedIds'];

// Update existing days
foreach ($updatedDays as $day) {
    $id = intval($day['id']);
    $date = $day['date'];
    $hours = intval($day['hours']);
    $stmt = $conn->prepare("UPDATE time_off SET timeoff_note = ?, week_start = ?, assigned_hours = ? WHERE timeoff_id = ?");
    $stmt->bind_param('ssii', $newName, $date, $hours, $id);
    $stmt->execute();
}

// Insert new days
foreach ($newDays as $day) {
    $date = $day['date'];
    $hours = intval($day['hours']);
    $stmt = $conn->prepare("INSERT INTO time_off (timeoff_note, week_start, assigned_hours, is_global_timeoff) VALUES (?, ?, ?, 1)");
    $stmt->bind_param('ssi', $newName, $date, $hours);
    $stmt->execute();
}

// Delete removed days
foreach ($deletedIds as $id) {
    $id = intval($id);
    $stmt = $conn->prepare("DELETE FROM time_off WHERE timeoff_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
}

echo json_encode(['success' => true]);
?>