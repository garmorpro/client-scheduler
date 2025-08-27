<?php
require_once 'db.php';
session_start();

// LOG ACTIVITY FUNCTION
function logActivity($conn, $eventType, $user_id, $account_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // For service accounts, email and full_name may not apply, so we'll pass blanks
        $blank = '';
        $stmt->bind_param("sissss", $eventType, $user_id, $blank, $blank, $title, $description);
        $stmt->execute();
        $stmt->close();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_name = trim($_POST['account_name']);
    $password = $_POST['password'];

    // Check if service account exists
    $stmt = $conn->prepare("SELECT user_id, account_name, password, role, status FROM service_accounts WHERE account_name = ?");
    $stmt->bind_param("s", $account_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $account = $result->fetch_assoc();
        $user_id = $account['user_id'];
        $hashed_password = $account['password'];
        $role = strtolower(trim($account['role']));
        $status = strtolower(trim($account['status']));

        // Check if account is active
        if ($status !== 'active') {
            logActivity($conn, "failed_login", $user_id, $account_name, "Failed Login", "Account is inactive");
            $error = "Account is inactive. Contact administrator.";
        } elseif (password_verify($password, $hashed_password)) {
            // Successful login
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['account_name'] = $account_name;
            $_SESSION['role'] = $role;

            // Update last active timestamp
            $updateStmt = $conn->prepare("UPDATE service_accounts SET last_active = NOW() WHERE user_id = ?");
            $updateStmt->bind_param("i", $user_id);
            $updateStmt->execute();
            $updateStmt->close();

            // Log successful login
            logActivity($conn, "successful_login", $user_id, $account_name, "Service Account Login", "Successful login");

            // Redirect based on role
            if ($role === 'admin') {
                header("Location: /");
            } else {
                header("Location: /");
            }
            exit;
        } else {
            logActivity($conn, "failed_login", $user_id, $account_name, "Failed Login", "Incorrect password");
            $error = "Invalid login. Please try again.";
        }
    } else {
        logActivity($conn, "failed_login", null, $account_name, "Failed Login", "Account not found");
        $error = "Invalid login. Please try again.";
    }

    $stmt->close();
}
?>
