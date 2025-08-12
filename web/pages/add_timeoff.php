<?php
ob_start();  // Start output buffering

require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

function console_log($data) {
    // Commented out to avoid output before header redirect
    // $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    // echo "<script>console.log('PHP debug:', $json);</script>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // console_log($_POST);

    $userID = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $weekStart = $_POST['week_start'] ?? null;
    $reason = $_POST['reason'] ?? '';

    // console_log([
    //     'user_id' => $userID,
    //     'week_start' => $weekStart,
    //     'reason' => $reason,
    //     'types' => [
    //         'user_id' => gettype($userID),
    //         'week_start' => gettype($weekStart),
    //         'reason' => gettype($reason),
    //     ],
    // ]);

    if (!$userID || !$weekStart) {
        // echo "<script>console.error('Invalid input data: missing user or week.');</script>";
        die('Invalid input data: missing user or week.');
    }

    if (!isset($_POST['hours']) || !is_numeric($_POST['hours']) || floatval($_POST['hours']) <= 0) {
        // echo "<script>console.error('Invalid input data: hours must be a positive number.');</script>";
        die('Invalid input data: hours must be a positive number.');
    }
    $hoursOff = floatval($_POST['hours']);

    $reason = trim($reason);
    if ($reason === '') {
        $reason = null;
    }

    // console_log([
    //     'Preparing to insert:',
    //     'userID' => $userID,
    //     'weekStart' => $weekStart,
    //     'hoursOff' => $hoursOff,
    //     'reason' => $reason,
    // ]);

    $stmt = $conn->prepare("
        INSERT INTO time_off (user_id, week_start, hours_off, reason)
        VALUES (?, ?, ?, ?)
    ");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param('isds', $userID, $weekStart, $hoursOff, $reason);

    if ($stmt->execute()) {
        header("Location: master-schedule.php?status=success");
        exit();
    } else {
        die('Error adding time off: ' . $stmt->error);
    }
} else {
    die('Invalid request.');
}

ob_end_flush();  // Send output buffer
