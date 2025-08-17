<?php
require '../includes/db.php';

header("Content-Type: application/json");

// Read raw input
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

// Validate
if (!is_array($input) || !isset($input['entries']) || !is_array($input['entries'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "No entries provided"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO time_off (timeoff_note, week_start, assigned_hours, is_global_timeoff)
    VALUES (?, ?, ?, 1)
");

$results = [];
foreach ($input['entries'] as $entry) {
    $note = $entry['timeoff_note'] ?? '';
    $week_start = $entry['week_start'] ?? '';
    $assigned_hours = isset($entry['assigned_hours']) ? intval($entry['assigned_hours']) : 0;

    if (empty($week_start) || $assigned_hours <= 0) {
        $results[] = ["entry"=>$entry,"success"=>false,"id"=>null,"error"=>"Invalid week_start or hours"];
        continue;
    }

    $stmt->bind_param("ssi", $note, $week_start, $assigned_hours);
    $ok = $stmt->execute();

    $results[] = ["entry"=>$entry,"success"=>$ok,"id"=>$ok?$stmt->insert_id:null,"error"=>$ok?null:$stmt->error];
}

$stmt->close();
$conn->close();

echo json_encode(["success"=>true,"results"=>$results]);
