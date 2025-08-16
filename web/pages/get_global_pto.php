<?php

require_once '../includes/db.php'; // Adjust path as needed
header("Content-Type: application/json");



// --- Query for global time off ---
$sql = "SELECT id, timeoff_note, week_start, assigned_hours 
        FROM time_off 
        WHERE is_global = 1 
        ORDER BY timeoff_note, week_start";

$result = $conn->query($sql);

$entries = [];
while ($row = $result->fetch_assoc()) {
    $entries[] = $row;
}

echo json_encode($entries);
$conn->close();
