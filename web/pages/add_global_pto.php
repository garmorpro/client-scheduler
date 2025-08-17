<?php
require '../includes/db.php';

header("Content-Type: application/json");

// --- Read raw JSON input ---
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

// Validate input
if (!is_array($input) || !isset($input["entries"]) || !is_array($input["entries"]) || count($input["entries"]) === 0) {
    echo json_encode(["success" => false, "error" => "No entries provided"]);
    exit;
}

// Prepare insert (always global PTO)
$stmt = $conn->prepare("
    INSERT INTO time_off (timeoff_note, week_start, assigned_hours, is_global_timeoff)
    VALUES (?, ?, ?, 1)
");

if (!$stmt) {
    echo json_encode(["success" => false, "error" => $conn->error]);
    exit;
}

$results = [];
foreach ($input["entries"] as $entry) {
    $note          = $entry["timeoff_note"] ?? '';
    $week_start    = $entry["week_start"] ?? '';
    $assigned_hours = isset($entry["assigned_hours"]) ? intval($entry["assigned_hours"]) : 0;

    if (empty($week_start) || $assigned_hours <= 0) {
        $results[] = [
            "entry"   => $entry,
            "success" => false,
            "id"      => null,
            "error"   => "Missing or invalid week_start/assigned_hours"
        ];
        continue;
    }

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
