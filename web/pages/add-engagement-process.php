<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$employee = $_POST['employee']; // Use employee as the user ID
$engagementId = $_POST['client_name'];
$numberOfWeeks = isset($_POST['numberOfWeeks']) ? (int)$_POST['numberOfWeeks'] : 0;

if (!$employee) {
    die("Invalid user ID.");
}

// Insert into assignments table (without `assigned_hours`)
$assignmentInsert = $conn->prepare("INSERT INTO assignments (user_id, engagement_id) VALUES (?, ?)");
$assignmentInsert->bind_param("ii", $employee, $engagementId);
if (!$assignmentInsert->execute()) {
    die("Assignment creation failed: " . $conn->error);
}
$assignmentId = $assignmentInsert->insert_id;  // Get the ID of the inserted assignment

// Loop through the number of weeks and insert into assignment_weeks
for ($i = 1; $i <= $numberOfWeeks; $i++) {
    $weekKey = 'week_start_' . $i;
    $hoursKey = 'assigned_hours_' . $i;

    if (!isset($_POST[$weekKey]) || !isset($_POST[$hoursKey])) {
        continue;
    }

    $weekStart = $_POST[$weekKey];
    $assignedHours = $_POST[$hoursKey];

    // Insert into assignment_weeks
    $weekInsert = $conn->prepare("INSERT INTO assignment_weeks (assignment_id, week_start, assigned_hours) VALUES (?, ?, ?)");
    $weekInsert->bind_param("iss", $assignmentId, $weekStart, $assignedHours);
    if (!$weekInsert->execute()) {
        echo "Error inserting week $i: " . $conn->error . "<br>";
    }
}

header("Location: master-schedule.php");
exit();
?>
