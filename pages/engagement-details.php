<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (isset($_GET['id'])) {
    $engagementId = (int)$_GET['id'];

    // Permission holders can view any engagement; everyone else can only view
    // engagements they're actually staffed on (e.g. via the My Schedule page),
    // not an arbitrary engagement_id.
    if (!user_has_permission($conn, 'view_clients_engagements')) {
        $accessStmt = $conn->prepare("SELECT 1 FROM entries WHERE engagement_id = ? AND user_id = ? LIMIT 1");
        $accessStmt->bind_param('ii', $engagementId, $_SESSION['user_id']);
        $accessStmt->execute();
        $hasAccess = (bool) $accessStmt->get_result()->fetch_row();
        $accessStmt->close();

        if (!$hasAccess) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    $engagementQuery = "SELECT client_name, status, budgeted_hours, manager, notes FROM engagements WHERE engagement_id = ?";
    $stmt = $conn->prepare($engagementQuery);
    $stmt->bind_param('i', $engagementId);
    $stmt->execute();
    $engagementResult = $stmt->get_result();
    $engagement = $engagementResult->fetch_assoc();

    // Assigned employees + their hours + role, for the View Engagement modal.
    // Ordered by role seniority (manager > senior > staff > intern), not hours.
    $employeeQuery = "SELECT u.full_name, u.role, SUM(a.assigned_hours) AS total_hours
                      FROM entries a
                      JOIN users u ON a.user_id = u.user_id
                      WHERE a.engagement_id = ?
                      GROUP BY a.user_id, u.full_name, u.role
                      ORDER BY CASE u.role
                          WHEN 'manager' THEN 1
                          WHEN 'senior' THEN 2
                          WHEN 'staff' THEN 3
                          WHEN 'intern' THEN 4
                          ELSE 5
                      END, u.full_name ASC";
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
        'manager' => $engagement['manager'] ?? '',
        'assigned_employees' => $assignedEmployees,
        'notes' => $engagement['notes'] ?? ''
    ]);
}
