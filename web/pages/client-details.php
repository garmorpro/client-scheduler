<?php
require_once '../includes/db.php';

if (isset($_GET['id'])) {
    $clientId = $_GET['id'];

    // Get client name and total assigned hours
    $clientQuery = "SELECT client_name FROM engagements WHERE engagement_id = ?";
    $stmt = $conn->prepare($clientQuery);
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $clientResult = $stmt->get_result();
    $client = $clientResult->fetch_assoc();

    // Get assigned employees and their hours
    $employeeQuery = "SELECT u.first_name, u.last_name, a.assigned_hours 
                      FROM assignments a
                      JOIN users u ON a.user_id = u.user_id
                      WHERE a.engagement_id = ?";
    $stmt = $conn->prepare($employeeQuery);
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $employeeResult = $stmt->get_result();
    $assignedEmployees = '';
    while ($employee = $employeeResult->fetch_assoc()) {
        $assignedEmployees .= "<p>{$employee['first_name']} {$employee['last_name']} - {$employee['assigned_hours']} hrs</p>";
    }

    // Total assigned hours
    $totalHoursQuery = "SELECT SUM(assigned_hours) AS total_hours FROM assignments WHERE engagement_id = ?";
    $stmt = $conn->prepare($totalHoursQuery);
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $totalHoursResult = $stmt->get_result();
    $totalHours = $totalHoursResult->fetch_assoc()['total_hours'];

    // Return data as JSON
    echo json_encode([
        'client_name' => $client['client_name'],
        'total_hours' => $totalHours,
        'assigned_employees' => $assignedEmployees
    ]);
}
?>
