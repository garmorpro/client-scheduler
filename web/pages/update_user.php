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
    $userId = $_POST['user_id'] ?? null;
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? '';  // optional, if you track status in users table

    if (!$userId || $firstName === '' || $lastName === '' || $email === '' || $role === '') {
        die("Please fill all required fields.");
    }

    // Update user query
    $stmt = $conn->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?, email = ?, role = ?, status = ?
        WHERE user_id = ?
    ");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("sssssi", $firstName, $lastName, $email, $role, $status, $userId);

    if ($stmt->execute()) {
        $stmt->close();

        // Log activity
        $adminUserId = $_SESSION['user_id'] ?? null;
        $adminEmail = $_SESSION['email'] ?? '';
        $adminName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
        $role_uc = ucfirst($role);

        $title = "User Updated";
        $description = "Updated account for $firstName $lastName ($role_uc)";

        logActivity($conn, "user_updated", $adminUserId, $adminEmail, $adminName, $title, $description);

        header("Location: admin-panel.php?status=success");
        exit();
    } else {
        die("Error updating user: " . $stmt->error);
    }
} else {
    die('Invalid request.');
}
?>
