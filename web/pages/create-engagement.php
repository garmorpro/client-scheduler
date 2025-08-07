<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientName = $_POST['client_name'];
    $totalHours = $_POST['total_available_hours'];
    $assignedHours = $_POST['assigned_hours'];
    $status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';

    $stmt = $conn->prepare("INSERT INTO engagements (client_name, total_available_hours, assigned_hours, status, notes, last_updated, created) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("siiss", $clientName, $totalHours, $assignedHours, $status, $notes);
    $stmt->execute();

    header("Location: my-schedule.php");
    exit();
}
?>
