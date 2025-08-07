<?php
require_once '../includes/db.php';

if (isset($_GET['id'])) {
    $engagementId = $_GET['id'];

    // Get engagement name and total assigned hours
    $engagementQuery = "SELECT client_name, total_available_hours, notes FROM engagements WHERE engagement_id = ?";
    $stmt = $conn->prepare($engagementQuery);
    $stmt->bind_param('i', $engagementId);
    $stmt->execute();
    $engagementResult = $stmt->get_result();
    $engagement = $engagementResult->fetch_assoc();

    // Get assigned employees and their hours
    $employeeQuery = "SELECT u.first_name, u.last_name, SUM(a.assigned_hours) AS total_hours
                      FROM assignments a
                      JOIN users u ON a.user_id = u.user_id
                      WHERE a.engagement_id = ?
                      GROUP BY a.user_id";
    $stmt = $conn->prepare($employeeQuery);
    $stmt->bind_param('i', $engagementId);
    $stmt->execute();
    $employeeResult = $stmt->get_result();
    $assignedEmployees = '';
    while ($employee = $employeeResult->fetch_assoc()) {
        $name = htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']);
        $hours = htmlspecialchars($employee['total_hours']);
        $assignedEmployees .= "<p class='mb-1'><strong>{$name}</strong> – {$hours} hrs</p>";
    }

    // Total assigned hours
    $totalHoursQuery = "SELECT SUM(COALESCE(assigned_hours, 0)) AS total_hours FROM assignments WHERE engagement_id = ?";
    $stmt = $conn->prepare($totalHoursQuery);
    $stmt->bind_param('i', $engagementId);
    $stmt->execute();
    $totalHoursResult = $stmt->get_result();
    $totalHours = $totalHoursResult->fetch_assoc()['total_hours'];

    // Return data as JSON
    echo json_encode([
        'client_name' => $engagement['client_name'],
        'total_hours' => $totalHours,
        'max_hours' => $engagement['total_available_hours'],
        'assigned_employees' => $assignedEmployees,
        'notes' => $engagement['notes'] ?? ''
    ]);

}
?>
