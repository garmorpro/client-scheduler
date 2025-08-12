<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
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
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit();
    }
    $detailsStmt->bind_param('i', $deleteEngagementId);
    $detailsStmt->execute();
    $detailsStmt->bind_result($clientName, $notes);
    if (!$detailsStmt->fetch()) {
        $detailsStmt->close();
        echo json_encode(['success' => false, 'error' => 'Engagement not found']);
        exit();
    }
    $detailsStmt->close();

    // Delete engagement
    $stmt = $conn->prepare("DELETE FROM engagements WHERE engagement_id = ?");
    $stmt->bind_param('i', $deleteEngagementId);
    
    $currentUserId = $_SESSION['user_id'];
    $currentUserEmail = $_SESSION['email'] ?? '';
    $currentUserFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

    if ($stmt->execute()) {
        $title = "Engagement Deleted";
        $description = "Successfully deleted engagement for client: $clientName.";

        logActivity($conn, "successful_engagement_deleted", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

        echo json_encode(['success' => true]);
    } else {
        $title = "Failed Engagement Deletion";
        $description = "Failed to delete engagement for client: $clientName.";

        logActivity($conn, "failed_engagement_deleted", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

        echo json_encode(['success' => false, 'error' => 'Failed to delete engagement']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid engagement ID']);
    exit();
}
