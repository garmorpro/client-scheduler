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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientName = $_POST['client_name'];
    $totalHours = $_POST['total_available_hours'];
    $assignedHours = $_POST['assigned_hours'];
    $status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';

    $stmt = $conn->prepare("INSERT INTO engagements (client_name, total_available_hours, assigned_hours, status, notes, last_updated, created) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("siiss", $clientName, $totalHours, $assignedHours, $status, $notes);

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
            "Created engagement: " . $clientName
        );

        header("Location: my-schedule.php");
        exit();
    } else {
        echo "Error creating engagement: " . $stmt->error;
    }
}
?>
