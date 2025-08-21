<?php
require_once 'db.php';
session_start();

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
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password, first_name, last_name, role, mfa_enabled, change_password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];
        $hashed_password = $user['password'];
        $first_name = $user['first_name'];
        $last_name = $user['last_name'];
        $role = trim(strtolower($user['role']));
        $mfa_enabled = (int)$user['mfa_enabled'];
        $change_password = (int)$user['change_password'];

        if (password_verify($password, $hashed_password)) {

            // Force password change on first login
            if ($change_password === 1) {
                // Enable MFA automatically
                $updateMFA = $conn->prepare("UPDATE users SET mfa_enabled = 1 WHERE user_id = ?");
                $updateMFA->bind_param("i", $user_id);
                $updateMFA->execute();
                $updateMFA->close();

                // Store temporary session for password change
                $_SESSION['force_user_id'] = $user_id;
                $_SESSION['force_email'] = $email;
                $_SESSION['force_first_name'] = $first_name;
                $_SESSION['force_last_name'] = $last_name;
                $_SESSION['force_role'] = $role;

                header("Location: change-password.php");
                exit;
            }

            // MFA logic
            if ($mfa_enabled === 1) {
                $mfa_code = random_int(100000, 999999);
                $updateStmt = $conn->prepare("UPDATE users SET mfa_code = ? WHERE user_id = ?");
                $updateStmt->bind_param("ii", $mfa_code, $user_id);
                $updateStmt->execute();
                $updateStmt->close();

                $_SESSION['mfa_user_id'] = $user_id;
                header("Location: mfa.php");
                exit;
            }

            // Normal login
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['user_role'] = $role;
            $_SESSION['email'] = $email;

            $updateStmt = $conn->prepare("UPDATE users SET last_active = NOW() WHERE user_id = ?");
            $updateStmt->bind_param("i", $user_id);
            $updateStmt->execute();
            $updateStmt->close();

            $full_name = trim($first_name . ' ' . $last_name);
            logActivity($conn, "successful_login", $user_id, $email, $full_name, "User Login", "Successful login");

            if ($role === 'admin' || $role === 'manager') {
                header("Location: admin-panel.php");
            } else {
                header("Location: my-schedule.php");
            }
            exit;

        } else {
            $full_name = trim($first_name . ' ' . $last_name);
            logActivity($conn, "failed_login", $user_id, $email, $full_name, "Failed Login", "Incorrect password");
            $error = "Invalid login. Contact your administrator for account setup/troubleshooting.";
        }
    } else {
        logActivity($conn, "failed_login", null, $email, "", "Failed Login", "Email not found");
        $error = "Invalid login. Contact your administrator for account setup/troubleshooting.";
    }

    $stmt->close();
}
?>
