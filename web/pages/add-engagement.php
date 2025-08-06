<?php
require_once '../includes/db.php'; // DB connection
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$employee = isset($_GET['employee']) ? $_GET['employee'] : '';
$weekStart = isset($_GET['week']) ? $_GET['week'] : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $clientName = trim($_POST['client_name']);
    $assignedHours = intval($_POST['assigned_hours']);
    $startDate = $weekStart; // Week start date
    $endDate = date('Y-m-d', strtotime('+6 days', strtotime($startDate))); // Week end date

    // Insert new engagement into the database
    // $stmt = $conn->prepare("INSERT INTO engagements (client_name, assigned_hours, start_date, end_date, assigned_to) VALUES (?, ?, ?, ?, ?)");
    // $stmt->bind_param('sissi', $clientName, $assignedHours, $startDate, $endDate, $employee);
    // if ($stmt->execute()) {
    //     echo "<script>alert('Engagement added successfully!'); window.location.href = 'master-schedule.php';</script>";
    // } else {
    //     echo "<script>alert('Error adding engagement!');</script>";
    // }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Engagement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .form-select, .form-control { background-color: #f9fafb; border-radius: 8px; }
    </style>
</head>
<body>
  <?php include_once '../templates/sidebar.php'; ?>

  <div class="container mt-4">
    <h3 class="mb-4">Add Engagement</h3>

    <form method="POST">
        <div class="mb-3">
            <label for="client_name" class="form-label">Client Name</label>
            <input type="text" class="form-control" id="client_name" name="client_name" required>
        </div>

        <div class="mb-3">
            <label for="assigned_hours" class="form-label">Assigned Hours</label>
            <input type="number" class="form-control" id="assigned_hours" name="assigned_hours" required>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">Add Engagement</button>
        </div>
    </form>

    <a href="master-schedule.php" class="btn btn-link">Back to Schedule</a>
  </div>
</body>
</html>
