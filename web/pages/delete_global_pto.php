<?php
require_once '../includes/db.php'; // defines $conn
session_start();

// Optional: require login
if (!isset($_SESSION['user_id'])) {
    header("Location: admin-panel.php?error=unauthorized");
    exit();
}

// Validate input
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin-panel.php?error=invalid_id");
    exit();
}

$id = (int) $_GET['id'];

// Prepare statement
$stmt = mysqli_prepare($conn, "DELETE FROM time_off WHERE timeoff_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
        // Success → redirect back with success flag
        // header("Location: admin-panel.php?deleted=1");
        exit();
    } else {
        // Record not found or execute failed
        // header("Location: admin-panel.php?error=not_found");
        exit();
    }
    mysqli_stmt_close($stmt);
} else {
    // header("Location: admin-panel.php?error=prepare_failed");
    exit();
}

mysqli_close($conn);
