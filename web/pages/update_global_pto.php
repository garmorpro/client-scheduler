<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

// Accept POST data from FormData or JSON
if ($_SERVER['CONTENT_TYPE'] ?? '' === 'application/json') {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
}

$entry_id = $data['timeoff_id'] ?? null;
$week_start = $data['week_start'] ?? null;
$assigned_hours = $data['assigned_hours'] ?? null;
$timeoff_note = $data['timeoff_note'] ?? null;

if (!$entry_id) {
    echo json_encode(['success' => false, 'error' => 'Missing entry ID']);
    exit;
}

// Validate inputs
$entry_id = intval($entry_id);
$assigned_hours = is_numeric($assigned_hours) ? intval($assigned_hours) : 0;
$week_start = $conn->real_escape_string($week_start);
$timeoff_note = $conn->real_escape_string($timeoff_note);

// Update SQL
$sql = "UPDATE time_off 
        SET week_start = '$week_start', assigned_hours = '$assigned_hours', timeoff_note = '$timeoff_note' 
        WHERE timeoff_id = $entry_id AND is_global_timeoff = 1
        LIMIT 1";

if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
