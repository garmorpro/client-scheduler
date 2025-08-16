<?php
// add_global_pto.php
include '../includes/db.php'; // <-- adjust if your DB connection file has a different name

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get submitted values safely
    $week_start = $_POST['week_start'] ?? null;
    $assigned_hours = $_POST['assigned_hours'] ?? null;
    $timeoff_note = $_POST['timeoff_note'] ?? '';

    // Validate required fields
    if (!$week_start || !$assigned_hours) {
        die("Missing required fields.");
    }

    // Validate assigned_hours is numeric
    if (!is_numeric($assigned_hours) || $assigned_hours < 0) {
        die("Invalid hours.");
    }

    // Ensure week_start is a Monday
    $dateObj = strtotime($week_start);
    if (date('N', $dateObj) != 1) { // 1 = Monday
        die("Week start must be a Monday.");
    }

    // Prepare insert
    $stmt = $conn->prepare("
        INSERT INTO time_off (week_start, assigned_hours, timeoff_note, is_global_timeoff) 
        VALUES (?, ?, ?, 1)
    ");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sis", $week_start, $assigned_hours, $timeoff_note);

    if ($stmt->execute()) {
        // Redirect back to the page where modal is
        header("Location: your_page.php#globalPTOModal"); 
        exit();
    } else {
        die("Error inserting: " . $stmt->error);
    }
}
?>
