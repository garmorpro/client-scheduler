<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// LOG ACTIVITY FUNCTION
function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) 
            VALUES (?, ?, ?, ?, ?, ?)";
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

    if (!$userId || $firstName === '' || $lastName === '' || $email === '') {
        die("Please fill all required fields.");
    }

    // Update user query
    $stmt = $conn->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?, email = ?
        WHERE user_id = ?
    ");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("sssi", $firstName, $lastName, $email, $userId);

    if ($stmt->execute()) {
        $stmt->close();

        // ✅ Update session values if the logged-in user is the one being updated
        if ((int)$userId === (int)$_SESSION['user_id']) {
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name']  = $lastName;
            $_SESSION['email']      = $email;
        }

        // Log activity
        $adminUserId = $_SESSION['user_id'] ?? null;
        $adminEmail = $_SESSION['email'] ?? '';
        $adminRole = $_SESSION['user_role'] ?? '';
        $adminName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
        $role_uc = ucfirst($adminRole);

        $title = "User Updated";
        $description = "Updated account for $firstName $lastName ($role_uc)";

        logActivity($conn, "user_updated", $adminUserId, $adminEmail, $adminName, $title, $description);

        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?status=success");
        exit();
    } else {
        die("Error updating user: " . $stmt->error);
    }
} else {
    die('Invalid request.');
}
?>
