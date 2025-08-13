<?php
require_once '../includes/db.php';
session_start();

$error = '';

if (!isset($_SESSION['mfa_user_id'])) {
    header("Location: login.php");
    exit();
}

// Verify MFA code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['mfa_code']);
    $user_id = $_SESSION['mfa_user_id'];

    // Fetch MFA secret from database (you'll need a column mfa_secret)
    $stmt = $conn->prepare("SELECT mfa_secret, first_name, last_name, email, role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    require_once 'vendor/autoload.php'; // e.g., GoogleAuthenticator library

    $ga = new \PHPGangsta\GoogleAuthenticator();
    $checkResult = $ga->verifyCode($user['mfa_secret'], $code, 2); // 2 = 2*30sec tolerance

    if ($checkResult) {
        // MFA verified: complete login
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['user_role'] = strtolower($user['role']);
        $_SESSION['email'] = $user['email'];

        // Remove temporary MFA session
        unset($_SESSION['mfa_user_id'], $_SESSION['mfa_email'], $_SESSION['mfa_first_name'], $_SESSION['mfa_last_name'], $_SESSION['mfa_role']);

        // Update last_active
        $updateStmt = $conn->prepare("UPDATE users SET last_active = NOW() WHERE user_id = ?");
        $updateStmt->bind_param("i", $user_id);
        $updateStmt->execute();
        $updateStmt->close();

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

<!-- Simple HTML Form for MFA -->
<form method="POST">
    <h3>Multi-Factor Authentication</h3>
    <p>Enter the code from your authenticator app:</p>
    <input type="text" name="mfa_code" required>
    <button type="submit">Verify</button>
    <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
</form>
