if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employeeId = $_POST['user_id'] ?? null;
    $clientId = $_POST['engagement_id'] ?? null;
    $weekStart = $_POST['week_start'] ?? null;
    $assignedHours = $_POST['assigned_hours'] ?? null;
    $timeOffHours = $_POST['time_off_hours'] ?? null;
    $isTimeOff = isset($_POST['is_timeoff']) ? (int)$_POST['is_timeoff'] : 0;

    // Basic validation
    if (!$employeeId || !$weekStart) {
        die('Invalid input data.');
    }

    if ($isTimeOff) {
        // Time off entry
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
        // get client name for logging (similar to before)
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
        // Logging code (similar to your existing)
        // ...
        $title = $isTimeOff ? "Time Off Added" : "Assignment Added";
        $description = "1 week ({$descHours} hrs) added for employee ID {$employeeId} on {$descClient}.";

        logActivity($conn, $isTimeOff ? "timeoff_created" : "assignment_created", $_SESSION['user_id'], $_SESSION['email'] ?? '', trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')), $title, $description);

        header("Location: master-schedule.php?status=success");
        exit();
    } else {
        die('Error adding entry: ' . $stmt->error);
    }
}
