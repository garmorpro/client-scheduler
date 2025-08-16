<?php
header("Content-Type: application/json");

// --- DB connection ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "your_database";  // change this
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["error" => $conn->connect_error]));
}

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
