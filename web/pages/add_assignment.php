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

    if (!is_array($weeks) || !is_array($assignedHours) || !is_array($statuses)) {
        die('Invalid input data.');
    }

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
    $totalAssignedHours = 0;

    for ($i = 0; $i < count($weeks); $i++) {
        $weekStart = $weeks[$i];
        $hours = (float)$assignedHours[$i];
        $status = $statuses[$i];

        // Bind params with correct types:
        // i = int, i = int, s = string (date), d = double, s = string
        $stmt->bind_param('i i s d s', $employeeId, $clientId, $weekStart, $hours, $status);

        if ($stmt->execute()) {
            $successCount++;
            $totalAssignedHours += $hours;
        }
    }

    $stmt->close();

    if ($successCount > 0) {
        // Prepare log info
        $user_id = $_SESSION['user_id'];
        $email = $_SESSION['email'] ?? '';
        $full_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

        // Get employee full name for the log message
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

        // Fetch client name from engagements table
        $clientName = '';
        $stmtClient = $conn->prepare("SELECT client_name FROM engagements WHERE engagement_id = ?");
        if ($stmtClient) {
            $stmtClient->bind_param("i", $clientId);
            $stmtClient->execute();
            $stmtClient->bind_result($clientName);
            $stmtClient->fetch();
            $stmtClient->close();
        }

        $title = "Assignments Added";

        if ($successCount <= 1) {
            $description = "$successCount week ($totalAssignedHours hrs) added for $employeeFullName on $clientName engagement.";
        } else {
            $description = "$successCount weeks ($totalAssignedHours hrs) added for $employeeFullName on $clientName engagement.";
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
