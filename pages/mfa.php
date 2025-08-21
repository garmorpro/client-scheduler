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

        // Clear MFA code in DB and update last_active
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MFA Verification - Client Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: rgba(216, 216, 216, 1);">
<div class="container h-100 d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card p-3 shadow" style="width: 100%; max-width: 425px;">
        <img src="../assets/images/aarc-360-logo-1.webp" alt="" class="mx-auto d-block" style="width: 50%;">
        <div class="mt-4"></div>
        <h5 class="text-center mb-2">Multi-Factor Authentication</h5>
        <p class="text-center text-muted">Enter the 6-digit code sent to you to complete login.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="p-4" method="POST" action="">
            <div class="mb-3">
                <label for="mfa_code" class="form-label">6-Digit Code</label>
                <input type="text" class="form-control" name="mfa_code" placeholder="Enter code" maxlength="6" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn" style="background-color: rgb(23,62,70); color: white;">Verify</button>
            </div>
        </form>

        <p class="text-center text-muted mt-3">Contact your administrator if you have issues receiving your code.</p>
        <p class="text-center text-muted" style="font-size: 10px;">This is a demo application.</p>
    </div>
</div>
</body>
</html>
