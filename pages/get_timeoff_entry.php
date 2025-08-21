<?php
require_once '../includes/db.php'; // assumes $conn is mysqli connection
header('Content-Type: application/json');

// Read JSON input
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;
$week_start = $data['week_start'] ?? null;

if (!$user_id || !$week_start) {
    echo json_encode(['success' => false, 'error' => 'Missing user_id or week_start']);
    exit;
}

// Escape input
$user_id = $conn->real_escape_string($user_id);
$week_start = $conn->real_escape_string($week_start);

$sql = "SELECT timeoff_id, assigned_hours 
        FROM time_off 
        WHERE user_id = '$user_id' 
          AND week_start = '$week_start' 
          AND is_global_timeoff = 0
        LIMIT 1";

$result = $conn->query($sql);

if ($result) {
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'timeoff_id' => $row['timeoff_id'],
            'assigned_hours' => $row['assigned_hours'] // <-- include assigned_hours
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'entrtimeoff_idy_id' => null,
            'assigned_hours' => 0 // <-- no timeoff, return 0
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
