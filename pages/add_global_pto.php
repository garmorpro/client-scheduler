<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$entries = $_POST['entries'] ?? [];

if (!is_array($entries) || count($entries) === 0) {
    header("Location: admin-panel.php#time_off#global_pto");
    exit;
}

$stmt = $conn->prepare("INSERT INTO time_off (timeoff_note, week_start, assigned_hours, is_global_timeoff) VALUES (?, ?, ?, 1)");
if (!$stmt) {
    die("DB prepare failed: " . $conn->error);
}

foreach ($entries as $entry) {
    $note = $entry['timeoff_note'] ?? '';
    $week_start = $entry['week_start'] ?? '';
    $assigned_hours = isset($entry['assigned_hours']) ? intval($entry['assigned_hours']) : 0;

    if (!empty($week_start) && $assigned_hours > 0) {
        $stmt->bind_param("ssi", $note, $week_start, $assigned_hours);
        if (!$stmt->execute()) {
            error_log("DB insert failed for week_start {$week_start}: " . $stmt->error);
        }
    }
}

$stmt->close();
$conn->close();

// Redirect back to admin panel
header("Location: admin-panel.php#time_off");
exit;
