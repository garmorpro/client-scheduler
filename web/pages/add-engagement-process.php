<?php
require_once '../includes/db.php'; // Include database connection
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employeeId = $_POST['employee'];
    $clientId = $_POST['client_name'];
    $weeks = $_POST['weeks']; // Array of week start dates
    $assignedHours = $_POST['assigned_hours']; // Array of assigned hours
    $statuses = $_POST['statuses']; // Array of statuses

    if (count($weeks) !== count($assignedHours) || count($weeks) !== count($statuses)) {
        die('The number of weeks, assigned hours, and statuses do not match.');
    }

    $stmt = $conn->prepare("
        INSERT INTO assignments (user_id, engagement_id, week_start, assigned_hours, status)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $successCount = 0;
    for ($i = 0; $i < count($weeks); $i++) {
        $weekStart = $weeks[$i];
        $hours = $assignedHours[$i];
        $status = $statuses[$i];

        $stmt->bind_param('iisis', $employeeId, $clientId, $weekStart, $hours, $status);
        if ($stmt->execute()) {
            $successCount++;
        }
    }

    $stmt->close();

    if ($successCount > 0) {
        // Prepare log info
        $user_id = $_SESSION['user_id'];
        $email = $_SESSION['email'] ?? '';
        $full_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

        // Optional: get employee full name for the log message
        $empStmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
        if ($empStmt) {
            $empStmt->bind_param("i", $employeeId);
            $empStmt->execute();
            $empStmt->bind_result($empFirstName, $empLastName);
            $empStmt->fetch();
            $empStmt->close();
        } else {
            $empFirstName = '';
            $empLastName = '';
        }
        $employeeFullName = trim("$empFirstName $empLastName");

        $title = "Assignments Added";
        // Fetch client name from engagements table
        $clientName = '';
        $sql = "SELECT client_name FROM engagements WHERE engagement_id = ?";
        if ($stmtClient = $conn->prepare($sql)) {
            $stmtClient->bind_param("i", $clientId);
            $stmtClient->execute();
            $stmtClient->bind_result($clientName);
            $stmtClient->fetch();
            $stmtClient->close();
        }
        
        if ($successCount <= 1) {
            $description = "$successCount assignment added for $employeeFullName on engagement \"$clientName\"";
        } else {
            $description = "$successCount assignments added for $employeeFullName on engagement \"$clientName\"";
        }


        logActivity($conn, "assignment_created", $user_id, $email, $full_name, $title, $description);

        header("Location: master-schedule.php?status=success");
        exit();
    } else {
        die('Error adding assignments.');
    }
} else {
    die('Invalid request.');
}
