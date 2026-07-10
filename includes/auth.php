<?php
require_once 'db.php';
require_once 'session_init.php';

// LOG ACTIVITY FUNCTION
function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        $stmt->execute();
        $stmt->close();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT user_id, full_name, email, password, role, status, theme_mode FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $account = $result->fetch_assoc();
        $user_id = $account['user_id'];
        $full_name = trim($account['full_name'] ?? '');
        $nameParts = $full_name === '' ? ['', ''] : array_pad(preg_split('/\s+/', $full_name, 2), 2, '');
        $first_name = $nameParts[0];
        $last_name = $nameParts[1];
        $hashed_password = $account['password'];
        $role = strtolower(trim($account['role'] ?? ''));
        $status = strtolower(trim($account['status'] ?? ''));
        $theme_mode = strtolower(trim($account['theme_mode'] ?? '')) ?: 'light';

        // Check if account is active
        if ($status !== 'active') {
            logActivity($conn, "failed_login", $user_id, $email, $full_name, "Failed Login", "Account is inactive");
            $error = "Account is inactive. Contact administrator.";
        } elseif (password_verify($password, $hashed_password)) {
            session_regenerate_id(true);

            // Accounts are provisioned with a shared default password ("change_me");
            // there's no dedicated "verified" column, so we detect the default by
            // checking whether the stored hash still matches it.
            if (password_verify('change_me', $hashed_password)) {
                $_SESSION['pending_user_id'] = $user_id;
                logActivity($conn, "password_change_required", $user_id, $email, $full_name, "Password Change Required", "First login, redirected to set password");
                header("Location: ../pages/change-password.php");
                exit;
            }

            // Successful login
            $_SESSION['user_id'] = $user_id;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $account['email'];
            $_SESSION['user_role'] = $role;
            $_SESSION['theme'] = $theme_mode;

            // Update last active timestamp
            $updateStmt = $conn->prepare("UPDATE users SET last_active = NOW() WHERE user_id = ?");
            $updateStmt->bind_param("i", $user_id);
            $updateStmt->execute();
            $updateStmt->close();

            // Log successful login
            logActivity($conn, "successful_login", $user_id, $email, $full_name, "Successful Login", "Successful login");

            header("Location: ../pages/my-schedule.php?welcome=1");
            exit;
        } else {
            logActivity($conn, "failed_login", $user_id, $email, $full_name, "Failed Login", "Incorrect password");
            $error = "Invalid login. Please try again.";
        }
    } else {
        logActivity($conn, "failed_login", null, $email, '', "Failed Login", "Account not found");
        $error = "Invalid login. Please try again.";
    }

    $stmt->close();
}
?>
