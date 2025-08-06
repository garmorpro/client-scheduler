<?php
require_once '../includes/db.php'; // Include database connection
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve the posted values
    $employeeId = $_POST['employee'];
    $clientId = $_POST['client_name'];
    $weeks = $_POST['weeks']; // Array of week start dates
    $assignedHours = $_POST['assigned_hours']; // Array of assigned hours for each week

    // Check if we have the same number of weeks and assigned hours
    if (count($weeks) !== count($assignedHours)) {
        die('The number of weeks and assigned hours do not match.');
    }

    // Loop through each week and assigned hours, inserting into the database
    $stmt = $conn->prepare("
        INSERT INTO assignments (user_id, engagement_id, week_start, assigned_hours, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    
    for ($i = 0; $i < count($weeks); $i++) {
        $weekStart = $weeks[$i];
        $hours = $assignedHours[$i];

        // Bind parameters and execute
        $stmt->bind_param('iisi', $employeeId, $clientId, $weekStart, $hours);
        $stmt->execute();
    }

    // Check if the insert was successful
    if ($stmt->affected_rows > 0) {
        header("Location: master-schedule.php?status=success");
    } else {
        die('Error adding assignments.');
    }
} else {
    die('Invalid request.');
}
?>
