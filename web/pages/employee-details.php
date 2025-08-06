<?php
require_once '../includes/db.php';

if (isset($_GET['id'])) {
    $employeeId = $_GET['id'];

    // Get employee name, role, and total available hours
    $employeeQuery = "SELECT first_name, last_name, role, max_hours FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($employeeQuery);
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $employeeResult = $stmt->get_result();
    $employee = $employeeResult->fetch_assoc();

    // Get the employee's upcoming assignments
    $assignmentsQuery = "SELECT engagement_name, assigned_hours, assignment_date 
                         FROM assignments a
                         JOIN engagements e ON a.engagement_id = e.engagement_id
                         WHERE a.user_id = ? AND assignment_date >= CURDATE() 
                         ORDER BY assignment_date";
    $stmt = $conn->prepare($assignmentsQuery);
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $assignmentsResult = $stmt->get_result();

    $upcomingAssignments = '';
    $totalAssignedHours = 0;
    while ($assignment = $assignmentsResult->fetch_assoc()) {
        $upcomingAssignments .= "<p class='mb-1'><strong>{$assignment['engagement_name']}</strong> – {$assignment['assigned_hours']} hrs on {$assignment['assignment_date']}</p>";
        $totalAssignedHours += $assignment['assigned_hours'];
    }

    // Return data as JSON
    echo json_encode([
        'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
        'role' => $employee['role'],
        'total_hours' => $totalAssignedHours,
        'max_hours' => $employee['max_hours'],
        'upcoming_assignments' => $upcomingAssignments
    ]);
}
?>
