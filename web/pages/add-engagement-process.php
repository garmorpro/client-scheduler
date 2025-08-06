<?php
require_once '../includes/db.php'; // Include database connection
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employeeId = $_POST['employee'];
    $clientId = $_POST['client_name'];
    $weeks = $_POST['weeks']; // Array of week start dates
    $assignedHours = $_POST['assigned_hours']; // Array of assigned hours
    $statuses = $_POST['statuses']; // Array of statuses

    if (count($weeks) !== count($assignedHours) || count($weeks) !== count($statuses)) {
        die('The number of weeks, assigned hours, and statuses do not match.');
    }

    $stmt = $conn->prepare("
        INSERT INTO assignments (user_id, engagement_id, week_start, assigned_hours, status)
        VALUES (?, ?, ?, ?, ?)
    ");

    for ($i = 0; $i < count($weeks); $i++) {
        $weekStart = $weeks[$i];
        $hours = $assignedHours[$i];
        $status = $statuses[$i];

        $stmt->bind_param('iisis', $employeeId, $clientId, $weekStart, $hours, $status);
        $stmt->execute();
    }

    if ($stmt->affected_rows > 0) {
        header("Location: master-schedule.php?status=success");
        exit();
    } else {
        die('Error adding assignments.');
    }
} else {
    die('Invalid request.');
}
?>
