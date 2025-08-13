<?php
require_once 'db.php';
session_start();

$error = '';

if (!isset($_SESSION['force_user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $user_id = $_SESSION['force_user_id'];

        $stmt = $conn->prepare("UPDATE users SET password = ?, change_password = 0 WHERE user_id = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        if ($stmt->execute()) {
            // Complete login
            $_SESSION['user_id'] = $user_id;
            $_SESSION['first_name'] = $_SESSION['force_first_name'];
            $_SESSION['last_name'] = $_SESSION['force_last_name'];
            $_SESSION['user_role'] = $_SESSION['force_role'];
            $_SESSION['email'] = $_SESSION['force_email'];

            // Remove temporary force-login session
            unset($_SESSION['force_user_id'], $_SESSION['force_first_name'], $_SESSION['force_last_name'], $_SESSION['force_role'], $_SESSION['force_email']);

            // Redirect based on role
            if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'manager') {
                header("Location: admin-panel.php");
            } else {
                header("Location: my-schedule.php");
            }
            exit;
        } else {
            $error = "Error updating password. Try again.";
        }
        $stmt->close();
    }
}
?>

<form method="POST">
    <h3>Change Your Password</h3>
    <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
    <div>
        <label>New Password</label>
        <input type="password" name="new_password" required>
    </div>
    <div>
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required>
    </div>
    <button type="submit">Update Password</button>
</form>
