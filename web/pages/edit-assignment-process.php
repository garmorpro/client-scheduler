<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assignmentId = $_POST['assignment_id'];
    $assignedHours = $_POST['assigned_hours'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE assignments SET assigned_hours = ?, status = ? WHERE assignment_id = ?");
    $stmt->bind_param('isi', $assignedHours, $status, $assignmentId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        header("Location: master-schedule.php?update=success");
    } else {
        header("Location: master-schedule.php?update=none");
    }
    exit();
} else {
    die('Invalid request.');
}
?>
