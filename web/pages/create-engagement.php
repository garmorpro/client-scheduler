<?php
require_once '../includes/db.php';

// Make sure session is started to access user info
session_start();

// Add the logActivity function here or include it from a common file
function logActivity($conn, $eventType, $userId, $username, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, username, title, description) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sisss", $eventType, $userId, $username, $title, $description);
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
    $stmt->bind_param("siiss", $clientName, $totalHours, $assignedHours, $status, $notes);

    if ($stmt->execute()) {
        // Log engagement creation
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['email'] ?? 'Unknown User';

        logActivity(
            $conn,
            "engagement_created",
            $userId,
            $username,
            "Engagement Created",
            "Created engagement: " . $clientName
        );

        header("Location: my-schedule.php");
        exit();
    } else {
        // Handle insert failure (optional)
        echo "Error creating engagement: " . $stmt->error;
    }
}
?>
