<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignmentId = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
    $assignedHours = isset($_POST['assigned_hours']) ? intval($_POST['assigned_hours']) : 0;

    if ($assignmentId > 0 && $assignedHours > 0) {
        $stmt = $conn->prepare("UPDATE assignments SET assigned_hours = ? WHERE assignment_id = ?");
        $stmt->bind_param("ii", $assignedHours, $assignmentId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "✅ Successfully updated assignment ID $assignmentId to $assignedHours hours.";
            } else {
                echo "⚠️ No rows updated. Double check that the assignment exists and values are different.";
            }
        } else {
            echo "❌ MySQL error: " . $stmt->error;
        }
    } else {
        echo "❌ Invalid input.";
    }
} else {
    echo "❌ Invalid request method.";
}
