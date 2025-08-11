<?php
require_once '../includes/db.php'; // Your DB connection file
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// LOG ACTIVITY FUNCTION
function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Check if assignment_id is set and valid
if (isset($_POST['assignment_id']) && is_numeric($_POST['assignment_id'])) {
    $assignmentId = (int)$_POST['assignment_id'];

    // First, fetch assignment details for logging
    $detailsStmt = $conn->prepare("SELECT user_id, engagement_id, assigned_hours, week_start FROM assignments WHERE assignment_id = ?");
    if (!$detailsStmt) {
        echo 'error';
        exit();
    }
    $detailsStmt->bind_param('i', $assignmentId);
    $detailsStmt->execute();
    $detailsStmt->bind_result($assignedUserId, $engagementId, $assignedHours, $weekStart);
    if (!$detailsStmt->fetch()) {
        // Assignment not found
        $detailsStmt->close();
        echo 'error';
        exit();
    }
    $detailsStmt->close();

    // Get employee name
    $empStmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
    $empFirstName = $empLastName = '';
    if ($empStmt) {
        $empStmt->bind_param("i", $assignedUserId);
        $empStmt->execute();
        $empStmt->bind_result($empFirstName, $empLastName);
        $empStmt->fetch();
        $empStmt->close();
    }
    $employeeFullName = trim("$empFirstName $empLastName");

    // Get client name
    $clientName = '';
    $clientStmt = $conn->prepare("SELECT client_name FROM engagements WHERE engagement_id = ?");
    if ($clientStmt) {
        $clientStmt->bind_param("i", $engagementId);
        $clientStmt->execute();
        $clientStmt->bind_result($clientName);
        $clientStmt->fetch();
        $clientStmt->close();
    }

    // Delete the assignment
    $stmt = $conn->prepare("DELETE FROM assignments WHERE assignment_id = ?");
    $stmt->bind_param('i', $assignmentId);
    if ($stmt->execute()) {
        // Log deletion
        $user_id = $_SESSION['user_id'];
        $email = $_SESSION['email'] ?? '';
        $full_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

        $title = "Assignment Deleted";
        $description = "Deleted assignment for $employeeFullName on $clientName engagement, week starting $weekStart, assigned hours: $assignedHours";

        logActivity($conn, "assignment_deleted", $user_id, $email, $full_name, $title, $description);

        echo 'success';
    } else {
        echo 'error';
    }
    $stmt->close();
} else {
    echo 'error';  // Invalid assignment_id
}
