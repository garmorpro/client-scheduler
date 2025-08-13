<?php
require_once '../includes/db.php';
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
    $entryId = $_POST['entry_id'];
    $assignedHours = $_POST['assigned_hours'];
    $status = $_POST['status'] ?? null;

    // Get current entry info for logging and to know if it is time off or not
    $infoSql = "
        SELECT a.user_id, a.engagement_id, a.week_start, u.first_name, u.last_name, e.client_name, a.is_timeoff
        FROM entries a
        LEFT JOIN users u ON a.user_id = u.user_id
        LEFT JOIN engagements e ON a.engagement_id = e.engagement_id
        WHERE a.entry_id = ?
    ";
    $infoStmt = $conn->prepare($infoSql);
    if (!$infoStmt) {
        die("Prepare failed for entry info: (" . $conn->errno . ") " . $conn->error);
    }
    $infoStmt->bind_param('i', $entryId);
    $infoStmt->execute();
    $infoStmt->bind_result($userId, $engagementId, $weekStart, $empFirstName, $empLastName, $clientName, $isTimeOff);
    $infoStmt->fetch();
    $infoStmt->close();

    // Validate $isTimeOff in case NULL
    $isTimeOff = $isTimeOff ? true : false;

    // Prepare update statement based on type
    $updateSql = "UPDATE entries SET assigned_hours = ? WHERE entry_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    if (!$updateStmt) {
        die("Prepare failed for update: (" . $conn->errno . ") " . $conn->error);
    }
    $updateStmt->bind_param('ii', $assignedHours, $entryId);
    $updateStmt->execute();

    if ($updateStmt->affected_rows > 0) {
        // Prepare log info
        $currentUserId = $_SESSION['user_id'];
        $email = $_SESSION['email'] ?? '';
        $full_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

        $employeeFullName = trim("$empFirstName $empLastName");
        $formattedWeekStart = date("m/d/Y", strtotime($weekStart));
        $title = "Entry Updated";

        $descClientName = $isTimeOff ? 'Time Off' : $clientName;
        $description = "Updated entry for $employeeFullName on $descClientName, week of $formattedWeekStart ({$assignedHours} hrs).";

        logActivity($conn, "entry_updated", $currentUserId, $email, $full_name, $title, $description);

        header("Location: master-schedule.php?update=success");
    } else {
        header("Location: master-schedule.php?update=none");
    }

    $updateStmt->close();
    exit();
} else {
    die('Invalid request.');
}
?>
