<?php
require_once '../includes/db.php'; // Your DB connection file
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if assignment_id is set and valid
if (isset($_POST['assignment_id']) && is_numeric($_POST['assignment_id'])) {
    $assignmentId = $_POST['assignment_id'];

    // Prepare the SQL statement to delete the assignment
    $stmt = $conn->prepare("DELETE FROM assignments WHERE assignment_id = ?");
    $stmt->bind_param('i', $assignmentId);
    
    if ($stmt->execute()) {
        echo 'success';  // Return success message
    } else {
        echo 'error';  // Return error message
    }
} else {
    echo 'error';  // Invalid assignment_id
}
?>
