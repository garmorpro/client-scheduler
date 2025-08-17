<?php
require '../includes/db.php';

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $week_start = $_POST['week_start'];
//     $assigned_hours = $_POST['assigned_hours'];
//     $timeoff_note = $_POST['timeoff_note'] ?? '';

//     $stmt = $conn->prepare("INSERT INTO time_off (week_start, assigned_hours, timeoff_note, is_global_timeoff) VALUES (?, ?, ?, 1)");
//     $stmt->bind_param("sis", $week_start, $assigned_hours, $timeoff_note);

//     if ($stmt->execute()) {
//         echo json_encode([
//             "success" => true,
//             "entry" => [
//                 "timeoff_id" => $stmt->insert_id,
//                 "week_start" => date("m/d/Y", strtotime($week_start)),
//                 "week_start_raw" => $week_start,
//                 "assigned_hours" => $assigned_hours,
//                 "timeoff_note" => $timeoff_note
//             ]
//         ]);
//     } else {
//         echo json_encode(["success" => false, "error" => $stmt->error]);
//     }
// }


header("Content-Type: application/json");

// --- Read raw JSON input ---
$input = json_decode(file_get_contents("php://input"), true);
if (!$input || !isset($input["entries"])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO time_off (timeoff_note, week_start, assigned_hours, is_global_timeoff) VALUES (?, ?, ?, ?)");

$results = [];
foreach ($input["entries"] as $entry) {
    $note = $entry["timeoff_note"];
    $week_start = $entry["week_start"];
    $assigned_hours = intval($entry["assigned_hours"]);
    $is_global = intval($entry["is_global_timeoff"]);

    $stmt->bind_param("ssii", $note, $week_start, $assigned_hours, $is_global);
    $ok = $stmt->execute();

    $results[] = [
        "entry" => $entry,
        "success" => $ok,
        "id" => $ok ? $stmt->insert_id : null,
        "error" => $ok ? null : $stmt->error
    ];
}

$stmt->close();
$conn->close();

echo json_encode(["results" => $results]);
