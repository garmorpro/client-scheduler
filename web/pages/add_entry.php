<?php
ob_start();
require_once '../includes/db.php';
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

function console_log($data) {
    echo "<script>console.log(" . json_encode($data) . ");</script>";
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
    $clientId = $_POST['engagement_id'] ?? null; // might be null for time off
    $weekStart = $_POST['week_start'] ?? null;

    console_log([
        'user_id' => $employeeId,
        'engagement_id' => $clientId,
        'week_start' => $weekStart,
    ]);

    console_log([
    'POST' => $_POST,
    'employeeId' => $employeeId,
    'clientId' => $clientId,
    'weekStart' => $weekStart,
    'assignedHours' => $assignedHours,
    'timeOffHours' => $timeOffHours,
    'hours' => $hours
]);

    // Validate format YYYY-MM-DD
    if (!$weekStart || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStart)) {
        console_log("Invalid or missing week_start date: " . $weekStart);
        die('Invalid or missing week_start date.');
    }

    function toPositiveFloat($val) {
    $f = floatval($val);
    return ($f > 0) ? $f : null;
}

$assignedHoursVal = toPositiveFloat($_POST['assigned_hours'] ?? '');
$timeOffHoursVal = toPositiveFloat($_POST['time_off_hours'] ?? '');

if ($assignedHoursVal !== null) {
    $hours = $assignedHoursVal;
} elseif ($timeOffHoursVal !== null) {
    $hours = $timeOffHoursVal;
} else {
    console_log("Hours must be submitted and > 0.");
    die('Hours must be submitted and > 0.');
}



    console_log([
        'assignedHours' => $assignedHours,
        'timeOffHours' => $timeOffHours,
        'final_hours' => $hours,
    ]);

    // Basic validation
    if (empty($employeeId) || empty($weekStart) || $hours <= 0) {
        die('Invalid input data.');
    }


    // Determine if time off or assignment
    $isTimeOff = (empty($clientId)) ? 1 : 0;

    // If no clientId (time off), set clientId to NULL or 0 depending on your DB design
    if ($isTimeOff) {
        $clientId = null; // or 0, if your DB uses zero
    }

    /// If $clientId is null, bind as null via 's' type and pass NULL
if ($isTimeOff) {
    $clientIdParam = null;
    $clientIdType = 's';
} else {
    $clientIdParam = $clientId;
    $clientIdType = 'i';
}

// Prepare statement with types accordingly:
$stmt = $conn->prepare("
    INSERT INTO assignments (user_id, engagement_id, week_start, assigned_hours, is_timeoff)
    VALUES (?, ?, ?, ?, ?)
");

if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

// Bind parameters with dynamic type for engagement_id
// 'i' for user_id, then $clientIdType for engagement_id, 's' for week_start, 'd' for hours, 'i' for is_timeoff

// Build types string dynamically:
$types = 'i' . $clientIdType . 'sdi';

// Note: if $clientIdParam is null, pass null; otherwise pass int

$stmt->bind_param($types, $employeeId, $clientIdParam, $weekStart, $hours, $isTimeOff);


    if ($stmt->execute()) {
        // Log activity (adjust description for time off vs assignment)
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

        if ($isTimeOff) {
            $title = "Time Off Added";
            $description = "1 week ({$hours} hrs) time off added for {$employeeFullName}.";
        } else {
            // Get client name
            $clientName = '';
            $stmtClient = $conn->prepare("SELECT client_name FROM engagements WHERE engagement_id = ?");
            if ($stmtClient) {
                $stmtClient->bind_param("i", $clientId);
                $stmtClient->execute();
                $stmtClient->bind_result($clientName);
                $stmtClient->fetch();
                $stmtClient->close();
            }
            $title = "Assignment Added";
            $description = "1 week ({$hours} hrs) added for {$employeeFullName} on {$clientName} engagement.";
        }

        logActivity($conn, $isTimeOff ? "timeoff_created" : "assignment_created", $user_id, $email, $full_name, $title, $description);

        header("Location: master-schedule.php?status=success");
exit();

ob_end_flush();
    } else {
        die('Error adding entry: ' . $stmt->error);
    }
} else {
    die('Invalid request.');
}
