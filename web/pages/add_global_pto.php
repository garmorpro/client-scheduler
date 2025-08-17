<?php
require '../includes/db.php';

header("Content-Type: application/json");

// --- Read raw JSON input ---
$input = json_decode(file_get_contents("php://input"), true);
if (!$input || empty($input["entries"])) {
    echo json_encode(["success" => false, "error" => "No entries provided"]);
    exit;
}

// Always insert as global PTO (is_global_timeoff = 1)
$stmt = $conn->prepare("
    INSERT INTO time_off (timeoff_note, week_start, assigned_hours, is_global_timeoff)
    VALUES (?, ?, ?, 1)
");

$results = [];
foreach ($input["entries"] as $entry) {
    $note = $entry["timeoff_note"] ?? '';
    $week_start = $entry["week_start"];
    $assigned_hours = intval($entry["assigned_hours"]);

    $stmt->bind_param("ssi", $note, $week_start, $assigned_hours);
    $ok = $stmt->execute();

    $results[] = [
        "entry"   => $entry,
        "success" => $ok,
        "id"      => $ok ? $stmt->insert_id : null,
        "error"   => $ok ? null : $stmt->error
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "results" => $results
]);
