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

            if ($mfa_enabled === 1) {
                // Generate 6-digit random code
                $mfa_code = random_int(100000, 999999);

                // Store it in the database
                $updateStmt = $conn->prepare("UPDATE users SET mfa_code = ? WHERE user_id = ?");
                $updateStmt->bind_param("ii", $mfa_code, $user_id);
                $updateStmt->execute();
                $updateStmt->close();

                // Store temporary session for MFA
                $_SESSION['mfa_user_id'] = $user_id;

                // Optionally: send code via email
                // mail($email, "Your MFA Code", "Your verification code is: $mfa_code");

                header("Location: mfa.php");
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
            $updateStmt->bind_param("i", $user_id);
            $updateStmt->execute();
            $updateStmt->close();

            // Log successful login
            $full_name = trim($first_name . ' ' . $last_name);
            logActivity($conn, "successful_login", $user_id, $email, $full_name, "User Login", "Successful login");

            // Redirect based on role
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
