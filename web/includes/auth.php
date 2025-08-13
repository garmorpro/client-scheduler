<?php
require_once 'db.php';
session_start();  // START SESSION AT TOP!

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

    $stmt = $conn->prepare("SELECT user_id, password, first_name, last_name, role, mfa_enabled FROM users WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

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

        if (password_verify($password, $hashed_password)) {

            // Check if MFA is enabled
            if ($mfa_enabled === 1) {
                // Store temporary session for MFA
                $_SESSION['mfa_user_id'] = $user_id;
                $_SESSION['mfa_email'] = $email;
                $_SESSION['mfa_first_name'] = $first_name;
                $_SESSION['mfa_last_name'] = $last_name;
                $_SESSION['mfa_role'] = $role;

                header("Location: mfa.php"); // MFA verification page
                exit;
            }

            // Normal login (no MFA)
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['user_role'] = $role;
            $_SESSION['email'] = $email;

            // Update last_active
            $updateStmt = $conn->prepare("UPDATE users SET last_active = NOW() WHERE user_id = ?");
            if ($updateStmt) {
                $updateStmt->bind_param("i", $user_id);
                $updateStmt->execute();
                $updateStmt->close();
            }

            // Log successful login
            $full_name = trim($first_name . ' ' . $last_name);
            logActivity($conn, "successful_login", $user_id, $email, $full_name, "User Login", "Successful login");

            // Role-based redirect
            if ($role === 'admin' || $role === 'manager') {
                header("Location: admin-panel.php");
            } else {
                header("Location: my-schedule.php");
            }
            exit;

        } else {
            // Incorrect password
            $full_name = trim($first_name . ' ' . $last_name);
            logActivity($conn, "failed_login", $user_id, $email, $full_name, "Failed Login", "Incorrect password");
            $error = "Invalid login. Contact your administrator for account setup/troubleshooting.";
        }
    } else {
        // Email not found
        logActivity($conn, "failed_login", null, $email, "", "Failed Login", "Email not found");
        $error = "Invalid login. Contact your administrator for account setup/troubleshooting.";
    }

    $stmt->close();
}
?>
