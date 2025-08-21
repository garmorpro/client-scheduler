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

if (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
    $deleteUserId = (int)$_POST['user_id'];

    // Fetch user details before deletion
    $detailsStmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = ?");
    if (!$detailsStmt) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit();
    }
    $detailsStmt->bind_param('i', $deleteUserId);
    $detailsStmt->execute();
    $detailsStmt->bind_result($firstName, $lastName, $emailAddress);
    if (!$detailsStmt->fetch()) {
        $detailsStmt->close();
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }
    $detailsStmt->close();

    $userFullName = trim("$firstName $lastName");

    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $deleteUserId);
    
    $currentUserId = $_SESSION['user_id'];
    $currentUserEmail = $_SESSION['email'] ?? '';
    $currentUserFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

    if ($stmt->execute()) {
        $title = "User Deleted";
        $description = "Deleted user $userFullName ($emailAddress).";

        logActivity($conn, "user_deleted", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

        echo json_encode(['success' => true]);
    } else {
        $title = "Failed User Deletion";
        $description = "Failed to delete user $userFullName ($emailAddress).";

        logActivity($conn, "failed_user_deleted", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

        echo json_encode(['success' => false, 'error' => 'Failed to delete user']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit();
}
