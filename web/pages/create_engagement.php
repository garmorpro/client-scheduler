<?php
require_once '../includes/db.php';

session_start();  // START SESSION AT TOP!

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

// Generate a unique 6-digit idno
function generateUniqueIdno($conn) {
    do {
        $idno = random_int(100000, 999999); // 6-digit number

        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM engagements WHERE idno = ?");
        if (!$checkStmt) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $checkStmt->bind_param("i", $idno);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();
    } while ($count > 0);

    return $idno;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $clientName = trim($_POST['client_name'] ?? '');
    $totalHours = $_POST['total_available_hours'] ?? 0;
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    // assigned_hours is not in modal, so default to 0 here
    $assignedHours = 0;

    // Basic validation
    if ($clientName === '' || $totalHours === '' || $status === '') {
        die("Please fill all required fields.");
    }

    // Generate unique idno
    $idno = generateUniqueIdno($conn);

    $stmt = $conn->prepare("INSERT INTO engagements (idno, client_name, total_available_hours, assigned_hours, status, notes, last_updated, created) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    // Bind parameters: i = int, s = string, i = int, i = int, s = string, s = string
    $stmt->bind_param("isisss", $idno, $clientName, $totalHours, $assignedHours, $status, $notes);

    if ($stmt->execute()) {
        $user_id = $_SESSION['user_id'] ?? null;
        $email = $_SESSION['email'] ?? '';
        $full_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

        logActivity(
            $conn,
            "engagement_created",
            $user_id,
            $email,
            $full_name,
            "Engagement Created",
            "Created engagement: " . $clientName . " with IDNO: " . $idno
        );

        header("Location: my-schedule.php?status=success");
        exit();
    } else {
        echo "Error creating engagement: " . $stmt->error;
    }
} else {
    die('Invalid request method.');
}
