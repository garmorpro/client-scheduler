<?php
require_once '../includes/db.php';
session_start();

$error = '';

if (!isset($_SESSION['mfa_user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['mfa_code']);
    $user_id = $_SESSION['mfa_user_id'];

    // Fetch MFA code from database
    $stmt = $conn->prepare("SELECT mfa_code, first_name, last_name, email, role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && $user['mfa_code'] == $code) {
        // MFA verified: complete login
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['user_role'] = strtolower($user['role']);
        $_SESSION['email'] = $user['email'];

        // Clear MFA code in DB
        $updateStmt = $conn->prepare("UPDATE users SET mfa_code = NULL, last_active = NOW() WHERE user_id = ?");
        $updateStmt->bind_param("i", $user_id);
        $updateStmt->execute();
        $updateStmt->close();

        // Remove temporary MFA session
        unset($_SESSION['mfa_user_id']);

        // Log successful login
        $full_name = trim($user['first_name'] . ' ' . $user['last_name']);
        $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissss", $eventType = "successful_login", $user_id, $user['email'], $full_name, $title = "User Login", $description = "Successful login (MFA)");
        $stmt->execute();
        $stmt->close();

        // Redirect based on role
        $role = strtolower($user['role']);
        if ($role === 'admin' || $role === 'manager') {
            header("Location: admin-panel.php");
        } else {
            header("Location: my-schedule.php");
        }
        exit;

    } else {
        $error = "Invalid MFA code. Please try again.";
    }
}
?>

<form method="POST">
    <h3>Multi-Factor Authentication</h3>
    <p>Enter the 6-digit code sent to you:</p>
    <input type="text" name="mfa_code" required maxlength="6">
    <button type="submit">Verify</button>
    <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
</form>
