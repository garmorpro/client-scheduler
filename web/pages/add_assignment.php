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
    $isTimeOff = isset($_POST['is_timeoff']) ? (int)$_POST['is_timeoff'] : 1;

    if (!$employeeId || !$weekStart) {
        die('Invalid input data.');
    }

    if ($isTimeOff === 1) {
        // Time Off entry
        $timeOffHours = $_POST['time_off_hours'] ?? null;
        if ($timeOffHours === null || $timeOffHours === '') {
            die('Please enter time off hours.');
        }

        $stmt = $conn->prepare("
            INSERT INTO assignments (user_id, engagement_id, week_start, assigned_hours, is_timeoff)
            VALUES (?, NULL, ?, ?, 1)
        ");
        if (!$stmt) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }

        $stmt->bind_param('isd', $employeeId, $weekStart, $timeOffHours);
        $descHours = $timeOffHours;
        $descClient = 'Time Off';

    } else {
        // Assignment entry
        $clientId = $_POST['engagement_id'] ?? null;
        $assignedHours = $_POST['assigned_hours'] ?? null;

        if (!$clientId || !$assignedHours) {
            die('Please select a client and enter assigned hours.');
        }

        $stmt = $conn->prepare("
            INSERT INTO assignments (user_id, engagement_id, week_start, assigned_hours, is_timeoff)
            VALUES (?, ?, ?, ?, 0)
        ");
        if (!$stmt) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }

        $stmt->bind_param('iisd', $employeeId, $clientId, $weekStart, $assignedHours);
        $descHours = $assignedHours;

        // Get client name for logging
        $clientName = '';
        $stmtClient = $conn->prepare("SELECT client_name FROM engagements WHERE engagement_id = ?");
        if ($stmtClient) {
            $stmtClient->bind_param("i", $clientId);
            $stmtClient->execute();
            $stmtClient->bind_result($clientName);
            $stmtClient->fetch();
            $stmtClient->close();
        }
        $descClient = $clientName;
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

        $title = $isTimeOff ? "Time Off Added" : "Assignment Added";
        $description = "1 week ({$descHours} hrs) added for {$employeeFullName} on {$descClient}.";

        logActivity($conn, $isTimeOff ? "timeoff_created" : "assignment_created", $user_id, $email, $full_name, $title, $description);

        header("Location: master-schedule.php?status=success");
        exit();
    } else {
        die('Error adding assignment: ' . $stmt->error);
    }
} else {
    die('Invalid request.');
}
