<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectName = $_POST['project_name'];
    $client = $_POST['client'];
    $type = $_POST['type'];
    $status = $_POST['status'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $estimatedHours = $_POST['estimated_hours'];
    $description = $_POST['description'] ?? '';

    $stmt = $conn->prepare("INSERT INTO engagements (project_name, client_name, type, status, start_date, end_date, total_available_hours, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssis", $projectName, $client, $type, $status, $startDate, $endDate, $estimatedHours, $description);
    $stmt->execute();

    header("Location: my-schedule.php");
    exit();
}
?>
