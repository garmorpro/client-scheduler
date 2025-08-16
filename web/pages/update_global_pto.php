<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

// Accept POST data from FormData
$timeoff_id = $_POST['timeoff_id'] ?? null;
$week_start = $_POST['week_start'] ?? null;
$assigned_hours = $_POST['assigned_hours'] ?? null;
$timeoff_note = $_POST['timeoff_note'] ?? null;

if (!$timeoff_id) {
    echo json_encode(['success' => false, 'error' => 'Missing entry ID']);
    exit;
}

// Validate inputs
$timeoff_id = intval($timeoff_id);
$assigned_hours = is_numeric($assigned_hours) ? intval($assigned_hours) : 0;
$week_start = $conn->real_escape_string($week_start);
$timeoff_note = $conn->real_escape_string($timeoff_note);

// Update SQL
$sql = "UPDATE time_off 
        SET week_start = '$week_start', assigned_hours = '$assigned_hours', timeoff_note = '$timeoff_note' 
        WHERE timeoff_id = $timeoff_id AND is_global_timeoff = 1
        LIMIT 1";

if ($conn->query($sql)) {
    // Return updated entry so JS can merge into accordion
    echo json_encode([
        'success' => true,
        'entry' => [
            'timeoff_id' => $timeoff_id,
            'week_start' => date('m/d/Y', strtotime($week_start)),
            'week_start_raw' => $week_start,
            'assigned_hours' => $assigned_hours,
            'timeoff_note' => $timeoff_note
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
