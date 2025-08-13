<?php
require_once '../includes/db.php'; // Your DB connection file
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/**
 * Log an activity event to the system_activity_log table
 */
function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST['entry_id']) && is_numeric($_POST['entry_id'])) {
    $entryId = (int)$_POST['entry_id'];

    // Fetch entry details for logging
    if ($detailsStmt = $conn->prepare("SELECT user_id, engagement_id, assigned_hours, week_start FROM entries WHERE entry_id = ?")) {
        $detailsStmt->bind_param('i', $entryId);
        $detailsStmt->execute();
        $detailsStmt->bind_result($assignedUserId, $engagementId, $assignedHours, $weekStart);
        
        if (!$detailsStmt->fetch()) {
            // entry not found
            $detailsStmt->close();
            echo 'error';
            exit();
        }
        $detailsStmt->close();
    } else {
        echo 'error';
        exit();
    }

    // Get employee full name
    $employeeFullName = '';
    if ($empStmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?")) {
        $empStmt->bind_param("i", $assignedUserId);
        $empStmt->execute();
        $empStmt->bind_result($empFirstName, $empLastName);
        if ($empStmt->fetch()) {
            $employeeFullName = trim("$empFirstName $empLastName");
        }
        $empStmt->close();
    }

    // Get client name
    $clientName = '';
    if ($clientStmt = $conn->prepare("SELECT client_name FROM engagements WHERE engagement_id = ?")) {
        $clientStmt->bind_param("i", $engagementId);
        $clientStmt->execute();
        $clientStmt->bind_result($clientName);
        $clientStmt->fetch();
        $clientStmt->close();
    }

    // Delete the entry
    if ($stmt = $conn->prepare("DELETE FROM entries WHERE entry_id = ?")) {
        $stmt->bind_param('i', $entryId);
        if ($stmt->execute()) {
            // Log deletion
            $user_id = $_SESSION['user_id'];
            $email = $_SESSION['email'] ?? '';
            $full_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

            $title = "Entry Deleted";
            $formattedWeekStart = date("m/d/Y", strtotime($weekStart));
            $description = "Deleted entry for $employeeFullName on $clientName, $formattedWeekStart ($assignedHours hrs).";

            logActivity($conn, "entry_deleted", $user_id, $email, $full_name, $title, $description);

            echo 'success';
        } else {
            echo 'error';
        }
        $stmt->close();
    } else {
        echo 'error';
    }

} else {
    echo 'error';  // Invalid entry_id
}
