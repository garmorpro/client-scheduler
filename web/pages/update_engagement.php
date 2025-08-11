<?php
require_once '../includes/db.php';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $engagementId = $_POST['engagement_id'] ?? null;
    $clientName = trim($_POST['client_name'] ?? '');
    $totalAvailableHours = trim($_POST['total_available_hours'] ?? '');
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    // Basic validation
    if (!$engagementId || $clientName === '' || $totalAvailableHours === '' || $status === '') {
        die("Please fill all required fields.");
    }

    // Validate numeric hours (optional)
    if (!is_numeric($totalAvailableHours) || $totalAvailableHours < 0) {
        die("Invalid total available hours.");
    }

    // Prepare update query
    $stmt = $conn->prepare("
        UPDATE engagements 
        SET client_name = ?, total_available_hours = ?, status = ?, notes = ?, last_updated = NOW()
        WHERE engagement_id = ?
    ");

    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("sdssi", $clientName, $totalAvailableHours, $status, $notes, $engagementId);

    if ($stmt->execute()) {
        $stmt->close();

        // Log activity
        $adminUserId = $_SESSION['user_id'];
        $adminEmail = $_SESSION['email'] ?? '';
        $adminName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

        $title = "Engagement Updated";
        $description = "Updated engagement \"$clientName\" with status \"$status\" and $totalAvailableHours hours";

        logActivity($conn, "engagement_updated", $adminUserId, $adminEmail, $adminName, $title, $description);

        // Redirect or respond success
        header("Location: admin-panel.php?status=engagement_updated");
        exit();
    } else {
        die("Error updating engagement: " . $stmt->error);
    }
} else {
    die('Invalid request.');
}
?>
