<?php
require_once '../includes/db.php';
session_start();

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

echo '<pre>';
print_r($_POST);
echo '</pre>';
exit;

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignmentId = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
    $assignedHours = isset($_POST['assigned_hours']) ? intval($_POST['assigned_hours']) : 0;

    if ($assignmentId > 0 && $assignedHours > 0) {
        $stmt = $conn->prepare("UPDATE assignments SET assigned_hours = ? WHERE assignment_id = ?");
        $stmt->bind_param("ii", $assignedHours, $assignmentId);

        if ($stmt->execute()) {
            // Redirect back to schedule or show a success message
            header("Location: master-schedule.php?updated=1");
            exit();
        } else {
            die("Error updating assignment: " . $conn->error);
        }
    } else {
        die("Invalid input.");
    }
} else {
    header("Location: ../master-schedule.php");
    exit();
}
?>
