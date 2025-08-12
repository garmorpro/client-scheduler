<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Activity logging function
function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST['engagement_id']) && is_numeric($_POST['engagement_id'])) {
    $deleteEngagementId = (int)$_POST['engagement_id'];

    // Fetch engagement details before deletion
    $detailsStmt = $conn->prepare("SELECT client_name, notes FROM engagements WHERE engagement_id = ?");
    if (!$detailsStmt) {
        echo 'error';
        exit();
    }
    $detailsStmt->bind_param('i', $deleteEngagementId);
    $detailsStmt->execute();
    $detailsStmt->bind_result($clientName, $notes);
    if (!$detailsStmt->fetch()) {
        // Engagement not found
        $detailsStmt->close();
        echo 'error';
        exit();
    }
    $detailsStmt->close();

    // Delete related assignments first
    $delAssignStmt = $conn->prepare("DELETE FROM assignments WHERE engagement_id = ?");
    if ($delAssignStmt) {
        $delAssignStmt->bind_param('i', $deleteEngagementId);
        $delAssignStmt->execute();
        $delAssignStmt->close();
    } else {
        // Log failure deleting assignments
        $currentUserId = $_SESSION['user_id'];
        $currentUserEmail = $_SESSION['email'] ?? '';
        $currentUserFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

        $title = "Failed Engagement Deletion";
        $description = "Failed to delete assignments for $clientName engagement.";

        logActivity($conn, "failed_engagement_deleted", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

        echo 'error';
        exit();
    }

    // Delete engagement
    $stmt = $conn->prepare("DELETE FROM engagements WHERE engagement_id = ?");
    $stmt->bind_param('i', $deleteEngagementId);
    if ($stmt->execute()) {
        // Log successful deletion
        $currentUserId = $_SESSION['user_id'];
        $currentUserEmail = $_SESSION['email'] ?? '';
        $currentUserFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

        $title = "Engagement Deleted";
        $description = "Deleted $clientName engagement.";

        logActivity($conn, "engagement_deleted", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

        echo 'success';
    } else {
        // Log failure deleting engagement
        $currentUserId = $_SESSION['user_id'];
        $currentUserEmail = $_SESSION['email'] ?? '';
        $currentUserFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

        $title = "Failed Engagement Deletion";
        $description = "Failed to delete $clientName engagement.";

        logActivity($conn, "failed_engagement_deleted", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

        echo 'error';
    }
    $stmt->close();
} else {
    echo 'error'; // Invalid engagement_id
}
