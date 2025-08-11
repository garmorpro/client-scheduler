<?php
require_once 'db.php';

function logActivity($conn, $eventType, $userId, $username, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, username, title, description) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sisss", $eventType, $userId, $username, $title, $description);
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
            session_start();
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['user_role'] = $role;
            $_SESSION['email'] = $email;

            // Log successful login
            logActivity($conn, "login", $user_id, $email, "User Login", "Successful login");

            header("Location: dashboard.php");
            exit;
        } else {
            
            logActivity($conn, "failed_login", $user_id, $email, "Failed Login", "Incorrect password attempt");
            $error = "Invalid login. Contact your administrator for account setup/troubleshooting.";
        }
    } else {
        $error = "Invalid login. Contact your administrator for account setup/troubleshooting.";
    }

    $stmt->close();
}
?>
