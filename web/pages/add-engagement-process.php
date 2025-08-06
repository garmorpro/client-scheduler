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

// Loop through the number of weeks and insert into assignments table
for ($i = 1; $i <= $numberOfWeeks; $i++) {
    $weekKey = 'week_start_' . $i;
    $hoursKey = 'assigned_hours_' . $i;

    if (!isset($_POST[$weekKey]) || !isset($_POST[$hoursKey])) {
        continue;
    }

    $weekStart = $_POST[$weekKey];
    $assignedHours = $_POST[$hoursKey];

    // Check if the assignment already exists
    $checkQuery = $conn->prepare("SELECT COUNT(*) FROM assignments WHERE user_id = ? AND engagement_id = ? AND week_start = ?");
    $checkQuery->bind_param("iis", $employee, $engagementId, $weekStart);
    $checkQuery->execute();
    $checkResult = $checkQuery->get_result();
    $exists = $checkResult->fetch_row()[0];

    if ($exists) {
        // Skip inserting this week if it already exists
        echo "Assignment for this week already exists: User {$employee}, Engagement {$engagementId}, Week Start {$weekStart}.<br>";
        continue;
    }

    // Insert into assignments table if it does not exist
    $assignmentInsert = $conn->prepare("INSERT INTO assignments (user_id, engagement_id, week_start, assigned_hours) VALUES (?, ?, ?, ?)");
    $assignmentInsert->bind_param("iiss", $employee, $engagementId, $weekStart, $assignedHours);
    if (!$assignmentInsert->execute()) {
        die("Assignment creation failed: " . $conn->error);
    }
}

header("Location: master-schedule.php");
exit();
?>
