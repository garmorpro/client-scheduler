<?php
require_once 'db.php';

session_start();  // START SESSION AT TOP!

// LOG ACTIVITY FUNCTION
function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password, first_name, last_name, role FROM users WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $hashed_password, $first_name, $last_name, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user_id;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['user_role'] = $role;
            $_SESSION['email'] = $email;

            // Log successful login with actual user info
            $full_name = trim($first_name . ' ' . $last_name);
            logActivity($conn, "successful_login", $user_id, $email, $full_name, "User Login", "Successful login");

            if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'manager') {
                header("Location: admin-panel.php");
            } else {
                header("Location: my-schedule.php");
            }
            exit;
        } else {
            // Log failed login with known user info (password incorrect)
            $full_name = trim($first_name . ' ' . $last_name);
            logActivity($conn, "failed_login", $user_id, $email, $full_name, "Failed Login", "Incorrect login attempt");
            $error = "Invalid login. Contact your administrator for account setup/troubleshooting.";
        }
    } else {
        // Log failed login due to email not found
        logActivity($conn, "failed_login", null, $email, "", "Failed Login", "Email not found");
        $error = "Invalid login. Contact your administrator for account setup/troubleshooting.";
    }

    $stmt->close();
}
?>
