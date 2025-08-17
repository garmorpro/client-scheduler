<?php
require '../includes/db.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

// Check if entries exist
if (!isset($_POST['entries']) || !is_array($_POST['entries']) || count($_POST['entries']) === 0) {
    header("Location: /pages/admin-panel.php#time_off#global_pto");
    exit;
}

$results = [];
$stmt = $conn->prepare("
    INSERT INTO time_off (timeoff_note, week_start, assigned_hours, is_global_timeoff)
    VALUES (?, ?, ?, 1)
");

if (!$stmt) {
    die("DB prepare failed: " . $conn->error);
}

// Insert each entry
foreach ($_POST['entries'] as $entry) {
    $note = $entry['timeoff_note'] ?? '';
    $week_start = $entry['week_start'] ?? '';
    $assigned_hours = isset($entry['assigned_hours']) ? intval($entry['assigned_hours']) : 0;

    if (!empty($week_start) && $assigned_hours > 0) {
        $stmt->bind_param("ssi", $note, $week_start, $assigned_hours);
        $stmt->execute();
        $results[] = true;
    } else {
        $results[] = false;
    }
}

$stmt->close();
$conn->close();

// After processing, redirect back to admin panel
header("Location: admin-panel.php#time_off#global_pto");
exit;
