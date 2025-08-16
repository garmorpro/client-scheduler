<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $week_start = $_POST['week_start'];
    $assigned_hours = $_POST['assigned_hours'];
    $timeoff_note = $_POST['timeoff_note'] ?? '';

    $stmt = $conn->prepare("INSERT INTO time_off (week_start, assigned_hours, timeoff_note, is_global_timeoff) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sis", $week_start, $assigned_hours, $timeoff_note);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "entry" => [
                "timeoff_id" => $stmt->insert_id,
                "week_start" => date("m/d/Y", strtotime($week_start)),
                "week_start_raw" => $week_start,
                "assigned_hours" => $assigned_hours,
                "timeoff_note" => $timeoff_note
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
}
