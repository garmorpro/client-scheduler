<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Helper function to output PHP variable to browser console
function console_log($data) {
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "<script>console.log('PHP debug:', $json);</script>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: log all POST data
    console_log($_POST);

    $userID = $_POST['user_id'] ?? null;
    $weekStart = $_POST['week_start'] ?? null;

    // Debug: log userID and weekStart individually
    console_log(['user_id' => $userID, 'week_start' => $weekStart]);

    $reason = $_POST['reason'] ?? '';

    if (!$userID || !$weekStart) {
        // Also output an error console log before die
        echo "<script>console.error('Invalid input data: missing user or week.');</script>";
        die('Invalid input data: missing user or week.');
    }

    if (!isset($_POST['hours']) || !is_numeric($_POST['hours']) || floatval($_POST['hours']) <= 0) {
        echo "<script>console.error('Invalid input data: hours must be a positive number.');</script>";
        die('Invalid input data: hours must be a positive number.');
    }
    $hoursOff = floatval($_POST['hours']);

    $reason = trim($reason);
    if ($reason === '') {
        $reason = null;
    }

    $stmt = $conn->prepare("
        INSERT INTO time_off (user_id, week_start, hours_off, reason)
        VALUES (?, ?, ?, ?)
    ");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    console_log([
    'userID (int cast)' => $userID,
    'weekStart' => $weekStart,
    'hoursOff' => $hoursOff,
    'reason' => $reason,
    'types' => [
        'userID' => gettype($userID),
        'weekStart' => gettype($weekStart),
        'hoursOff' => gettype($hoursOff),
        'reason' => gettype($reason),
    ],
]);

    // Binding parameters, 's' type for string fields, 'i' for int, 'd' for double
    $stmt->bind_param('isds', $userID, $weekStart, $hoursOff, $reason);

    if ($stmt->execute()) {
        // Log activity (no change here)
        $user_id = $_SESSION['user_id'];
        $email = $_SESSION['email'] ?? '';
        $full_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

        $empFirstName = '';
        $empLastName = '';
        $empStmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
        if ($empStmt) {
            $empStmt->bind_param("i", $userID);
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
