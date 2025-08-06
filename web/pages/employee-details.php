<?php
require_once '../includes/db.php';

if (isset($_GET['id'])) {
    $employeeId = $_GET['id'];

    // Get employee name
    $employeeQuery = "SELECT CONCAT(first_name, ' ', last_name) AS employee_name FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($employeeQuery);
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $employeeResult = $stmt->get_result();
    $employee = $employeeResult->fetch_assoc();

    // Get weekly assigned hours for employee
    $weekQuery = "SELECT week_start, SUM(assigned_hours) AS total_hours FROM assignments WHERE user_id = ? GROUP BY week_start ORDER BY week_start";
    $stmt = $conn->prepare($weekQuery);
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $weekResult = $stmt->get_result();
    $weeklyHours = '';
    while ($week = $weekResult->fetch_assoc()) {
        $weeklyHours .= "<p>Week of {$week['week_start']} - {$week['total_hours']} hrs</p>";
    }

    // Return data as JSON
    echo json_encode([
        'employee_name' => $employee['employee_name'],
        'weekly_hours' => $weeklyHours
    ]);
}
?>
