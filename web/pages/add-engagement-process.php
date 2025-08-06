<?php
require_once '../includes/db.php'; // Include database connection
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST variables from the modal form
    $employee = $_POST['employee'];
    $weekStart = $_POST['week_start'];
    $clientName = $_POST['client_name'];
    $assignedHours = $_POST['assigned_hours'];
    $engagementId = isset($_POST['engagement_id']) ? $_POST['engagement_id'] : null;

    // Validate input
    if (empty($clientName) || empty($assignedHours)) {
        die('Client name and assigned hours are required.');
    }

    // Check if engagementId exists to determine whether to insert or update
    if ($engagementId) {
        // Update existing engagement
        $query = "
            UPDATE engagements 
            SET client_name = ?, assigned_hours = ? 
            WHERE engagement_id = ?
        ";

        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die('MySQL prepare failed: ' . $conn->error);
        }
        $stmt->bind_param('ssi', $clientName, $assignedHours, $engagementId);
        $stmt->execute();
        $stmt->close();
        
        // Update assignment in assignment_weeks table
        $updateQuery = "
            UPDATE assignment_weeks 
            SET client_name = ?, assigned_hours = ? 
            WHERE engagement_id = ? AND week_start = ?
        ";

        $stmt = $conn->prepare($updateQuery);
        if ($stmt === false) {
            die('MySQL prepare failed: ' . $conn->error);
        }
        $stmt->bind_param('ssss', $clientName, $assignedHours, $engagementId, $weekStart);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new engagement
        $query = "
            INSERT INTO engagements (client_name, assigned_hours) 
            VALUES (?, ?)
        ";

        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die('MySQL prepare failed: ' . $conn->error);
        }
        $stmt->bind_param('si', $clientName, $assignedHours);
        $stmt->execute();
        $engagementId = $stmt->insert_id; // Get the last inserted ID
        $stmt->close();

        // Insert into assignment_weeks table
        $insertQuery = "
            INSERT INTO assignment_weeks (assignment_id, engagement_id, week_start, client_name, assigned_hours)
            VALUES (?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($insertQuery);
        if ($stmt === false) {
            die('MySQL prepare failed: ' . $conn->error);
        }
        $stmt->bind_param('iisss', $employee, $engagementId, $weekStart, $clientName, $assignedHours);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect back to the schedule page or show success message
    header("Location: master-schedule.php?start=" . $weekStart);
    exit();
}
?>
