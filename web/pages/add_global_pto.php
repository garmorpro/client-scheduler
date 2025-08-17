<?php
require '../includes/db.php';

header("Content-Type: application/json");

// Read raw input
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

// If no entries, just return empty results
if (!isset($input['entries']) || !is_array($input['entries']) || count($input['entries']) === 0) {
    echo json_encode(["success" => true, "results" => []]);
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
foreach ($input['entries'] as $entry) {
    $note           = $entry['timeoff_note'] ?? '';
    $week_start     = $entry['week_start'] ?? '';
    $assigned_hours = isset($entry['assigned_hours']) ? intval($entry['assigned_hours']) : 0;

    // Only insert valid entries
    if (!empty($week_start) && $assigned_hours > 0) {
        $stmt->bind_param("ssi", $note, $week_start, $assigned_hours);
        $stmt->execute();
        $results[] = [
            "entry"   => $entry,
            "success" => true,
            "id"      => $stmt->insert_id,
            "error"   => null
        ];
    } else {
        $results[] = [
            "entry"   => $entry,
            "success" => false,
            "id"      => null,
            "error"   => "Invalid week_start or assigned_hours"
        ];
    }
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,  // ALWAYS true for the whole request
    "results" => $results
]);
