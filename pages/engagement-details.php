<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (isset($_GET['id'])) {
    $engagementId = $_GET['id'];

    $engagementQuery = "SELECT client_name, status, budgeted_hours, notes FROM engagements WHERE engagement_id = ?";
    $stmt = $conn->prepare($engagementQuery);
    $stmt->bind_param('i', $engagementId);
    $stmt->execute();
    $engagementResult = $stmt->get_result();
    $engagement = $engagementResult->fetch_assoc();

    // Assigned employees + their hours + role, for the View Engagement modal
    $employeeQuery = "SELECT u.full_name, u.role, SUM(a.assigned_hours) AS total_hours
                      FROM entries a
                      JOIN users u ON a.user_id = u.user_id
                      WHERE a.engagement_id = ?
                      GROUP BY a.user_id, u.full_name, u.role
                      ORDER BY total_hours DESC";
    $stmt = $conn->prepare($employeeQuery);
    $stmt->bind_param('i', $engagementId);
    $stmt->execute();
    $employeeResult = $stmt->get_result();
    $assignedEmployees = [];
    while ($employee = $employeeResult->fetch_assoc()) {
        $assignedEmployees[] = [
            'name' => $employee['full_name'] ?? '',
            'role' => $employee['role'] ?? '',
            'hours' => (float)$employee['total_hours']
        ];
    }

    // Total assigned hours
    $totalHoursQuery = "SELECT SUM(COALESCE(assigned_hours, 0)) AS total_hours FROM entries WHERE engagement_id = ?";
    $stmt = $conn->prepare($totalHoursQuery);
    $stmt->bind_param('i', $engagementId);
    $stmt->execute();
    $totalHoursResult = $stmt->get_result();
    $totalHours = (float)($totalHoursResult->fetch_assoc()['total_hours'] ?? 0);

    echo json_encode([
        'client_name' => $engagement['client_name'] ?? '',
        'status' => $engagement['status'] ?? '',
        'total_hours' => $totalHours,
        'budgeted_hours' => (float)($engagement['budgeted_hours'] ?? 0),
        'assigned_employees' => $assignedEmployees,
        'notes' => $engagement['notes'] ?? ''
    ]);
}
