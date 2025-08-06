<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_POST['user_id']; // Get user_id from the POST request
$employee = $_POST['employee']; // Not needed in this case since user_id is already passed
$engagementId = $_POST['client_name'];
$numberOfWeeks = isset($_POST['numberOfWeeks']) ? (int)$_POST['numberOfWeeks'] : 0;

// We don't need to fetch the user_id from the database since it's already passed
// Make sure $userId is valid (if necessary)
if (!$userId) {
    die("Invalid user ID.");
}

// Insert into assignments table
$assignmentInsert = $conn->prepare("INSERT INTO assignments (user_id, engagement_id) VALUES (?, ?)");
$assignmentInsert->bind_param("ii", $userId, $engagementId);
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
