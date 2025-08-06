<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$employee = $_POST['employee'];
$engagementId = $_POST['client_name'];
$numberOfWeeks = isset($_POST['numberOfWeeks']) ? (int)$_POST['numberOfWeeks'] : 0;

// Get the user ID from employee name
$user_id_query = $conn->prepare("SELECT user_id FROM users WHERE first_name = ? LIMIT 1");
$user_id_query->bind_param("s", $employee);
$user_id_query->execute();
$user_id_result = $user_id_query->get_result();
$user_id_row = $user_id_result->fetch_assoc();
$user_id = $user_id_row ? $user_id_row['user_id'] : null;

if (!$user_id) {
    die("User not found.");
}

// Insert into assignments table
$assignmentInsert = $conn->prepare("INSERT INTO assignments (user_id, engagement_id) VALUES (?, ?)");
$assignmentInsert->bind_param("ii", $user_id, $engagementId);
if (!$assignmentInsert->execute()) {
    die("Assignment creation failed: " . $conn->error);
}
$assignmentId = $assignmentInsert->insert_id;

// Loop through the number of weeks and insert into assignments table
for ($i = 1; $i <= $numberOfWeeks; $i++) {
    $weekKey = 'week_start_' . $i;
    $hoursKey = 'assigned_hours_' . $i;

    if (!isset($_POST[$weekKey]) || !isset($_POST[$hoursKey])) {
        continue;
    }

    $weekStart = $_POST[$weekKey];
    $assignedHours = $_POST[$hoursKey];

    // Insert into assignments table (instead of assignment_weeks)
    $assignmentUpdate = $conn->prepare("UPDATE assignments SET week_start = ?, assigned_hours = ? WHERE assignment_id = ?");
    $assignmentUpdate->bind_param("ssi", $weekStart, $assignedHours, $assignmentId);
    if (!$assignmentUpdate->execute()) {
        echo "Error updating assignment $i: " . $conn->error . "<br>";
    }
}

header("Location: master-schedule.php");
exit();
