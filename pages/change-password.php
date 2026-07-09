<?php
require_once '../includes/db.php';
require_once '../includes/session_init.php';
require_once '../includes/csrf.php';

if (!isset($_SESSION['pending_user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['pending_user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = "Your session expired. Please try again.";
    } else {
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($newPassword === 'change_me') {
        $error = "Please choose a password other than the default.";
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed, $user_id);

        if ($stmt->execute()) {
            $stmt->close();

            $userStmt = $conn->prepare("SELECT full_name, email, role, theme_mode FROM users WHERE user_id = ?");
            $userStmt->bind_param("i", $user_id);
            $userStmt->execute();
            $user = $userStmt->get_result()->fetch_assoc();
            $userStmt->close();

            $full_name = trim($user['full_name'] ?? '');
            $nameParts = $full_name === '' ? ['', ''] : array_pad(preg_split('/\s+/', $full_name, 2), 2, '');
            $first_name = $nameParts[0];
            $last_name = $nameParts[1];
            $role = strtolower(trim($user['role'] ?? ''));
            $theme_mode = strtolower(trim($user['theme_mode'] ?? '')) ?: 'light';

            session_regenerate_id(true);
            unset($_SESSION['pending_user_id']);

            $_SESSION['user_id'] = $user_id;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $role;
            $_SESSION['theme'] = $theme_mode;

            $updateStmt = $conn->prepare("UPDATE users SET last_active = NOW() WHERE user_id = ?");
            $updateStmt->bind_param("i", $user_id);
            $updateStmt->execute();
            $updateStmt->close();

            $logStmt = $conn->prepare("INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)");
            $eventType = "password_changed";
            $title = "Password Changed";
            $description = "User set a new password on first login";
            $logStmt->bind_param("sissss", $eventType, $user_id, $user['email'], $full_name, $title, $description);
            $logStmt->execute();
            $logStmt->close();

            header("Location: my-schedule.php");
            exit;
        } else {
            $error = "Error updating password. Try again.";
            $stmt->close();
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - Client Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: rgba(216, 216, 216, 1);">
<div class="container h-100 d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card p-3 shadow" style="width: 100%; max-width: 425px;">
        <img src="../assets/images/aarc-360-logo-1.webp" alt="" class="mx-auto d-block" style="width: 50%;">
        <div class="mt-4"></div>
        <h5 class="text-center mb-2">Change Your Password</h5>
        <p class="text-center text-danger mt-2">For security, please set a new password before proceeding.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="p-4" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" name="new_password" placeholder="Enter new password" minlength="8" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm new password" minlength="8" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn" style="background-color: rgb(23,62,70); color: white;">Update Password</button>
            </div>
        </form>

        <p class="text-center text-muted mt-3">Contact your administrator if you experience any issues.</p>
    </div>
</div>
</body>
</html>
