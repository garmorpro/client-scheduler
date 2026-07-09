<?php

require_once '../includes/db.php'; // Adjust path as needed
require_once __DIR__ . '/../includes/session_init.php';
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// --- Query for global time off ---
$sql = "SELECT timeoff_id, timeoff_note, week_start, assigned_hours 
        FROM time_off 
        WHERE is_global_timeoff = 1 
        ORDER BY week_start";

$result = $conn->query($sql);

$entries = [];
while ($row = $result->fetch_assoc()) {
    $entries[] = $row;
}

echo json_encode($entries);
$conn->close();
