<?php
require_once 'db.php';

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
            session_regenerate_id(true); // prevent session fixation

            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['user_role'] = $role;
            $_SESSION['email'] = $email;

            // --- Add login event to system_activity_log ---
            $eventType = "login";
            $username = $email; // or "$first_name $last_name"
            $title = "User Login";
            $description = "Successful login";
                
            $logSql = "INSERT INTO system_activity_log (event_type, user_id, username, title, description) VALUES (?, ?, ?, ?, ?)";
            $logStmt = mysqli_prepare($conn, $logSql);
            if ($logStmt) {
                mysqli_stmt_bind_param($logStmt, "sisss", $eventType, $user_id, $username, $title, $description);
                mysqli_stmt_execute($logStmt);
                mysqli_stmt_close($logStmt);
            }
            // ----------------------------------------------

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid login. Contact your administrator for account setup/troubleshooting.";
        }
    } else {
        $error = "Invalid login. Contact your administrator for account setup/troubleshooting.";
    }

    $stmt->close();
}
?>
