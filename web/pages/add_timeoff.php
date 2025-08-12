<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employeeId = $_POST['user_id'] ?? null;
    $weekStart = $_POST['week_start'] ?? null;
    $reason = $_POST['reason'] ?? '';

    if (!$employeeId || !$weekStart) {
        die('Invalid input data: missing user or week.');
    }

    if (!isset($_POST['hours']) || !is_numeric($_POST['hours']) || floatval($_POST['hours']) <= 0) {
        die('Invalid input data: hours must be a positive number.');
    }
    $hoursOff = floatval($_POST['hours']);

    // Trim and convert empty reason to null
    $reason = trim($reason);
    if ($reason === '') {
        $reason = null;
    }

    $stmt = $conn->prepare("
        INSERT INTO time_off (employee_id, week_start, hours_off, reason)
        VALUES (?, ?, ?, ?)
    ");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    // Bind parameters carefully to allow NULL for reason
    if ($reason === null) {
        // 's' type needs to be passed as null explicitly
        $stmt->bind_param('isds', $employeeId, $weekStart, $hoursOff, $reason);
    } else {
        $stmt->bind_param('isds', $employeeId, $weekStart, $hoursOff, $reason);
    }

    if ($stmt->execute()) {
        // Log activity
        $user_id = $_SESSION['user_id'];
        $email = $_SESSION['email'] ?? '';
        $full_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

        // Get employee full name
        $empFirstName = '';
        $empLastName = '';
        $empStmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
        if ($empStmt) {
            $empStmt->bind_param("i", $employeeId);
            $empStmt->execute();
            $empStmt->bind_result($empFirstName, $empLastName);
            $empStmt->fetch();
            $empStmt->close();
        }
        $employeeFullName = trim("$empFirstName $empLastName");

        $title = "Time Off Added";
        $description = "{$hoursOff} hours time off added for {$employeeFullName} for week starting {$weekStart}.";

        logActivity($conn, "timeoff_created", $user_id, $email, $full_name, $title, $description);

        header("Location: master-schedule.php?status=success");
        exit();
    } else {
        die('Error adding time off: ' . $stmt->error);
    }
} else {
    die('Invalid request.');
}
