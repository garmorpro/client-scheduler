<?php
require_once '../includes/db.php';

// Log errors instead of displaying them
ini_set('log_errors', 1);
ini_set('display_errors', 0);
ini_set('error_log', __DIR__ . '/error_log.txt');
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing employee ID']);
    exit;
}

$employeeId = (int)$_GET['id'];
$today = date('Y-m-d');

// Get employee info
$stmt = $conn->prepare("SELECT first_name, last_name, role, total_available_hours FROM users WHERE user_id = ?");
$stmt->bind_param('i', $employeeId);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows === 0) {
    echo json_encode(['error' => 'Employee not found']);
    exit;
}

$user = $userResult->fetch_assoc();

// Get future assignments
$assignmentQuery = "
    SELECT e.client_name, a.assigned_hours, a.week_start
    FROM assignments a
    JOIN engagements e ON a.engagement_id = e.engagement_id
    WHERE a.user_id = ?
      AND a.week_start >= ?
    ORDER BY a.week_start ASC
";
$stmt = $conn->prepare($assignmentQuery);
$stmt->bind_param('is', $employeeId, $today);
$stmt->execute();
$assignmentResult = $stmt->get_result();

$assignments = [];
$totalAssignedHours = 0;

while ($row = $assignmentResult->fetch_assoc()) {
    $totalAssignedHours += $row['assigned_hours'];
    $assignments[] = [
        'client_name' => $row['client_name'],
        'assigned_hours' => $row['assigned_hours'],
        'week_start' => $row['week_start']
    ];
}

echo json_encode([
    'full_name' => $user['first_name'] . ' ' . $user['last_name'],
    'role' => $user['role'],
    'assignments' => $assignments,
    'total_assigned_hours' => $totalAssignedHours,
    'total_available_hours' => $user['total_available_hours'] ?? 1000,
]);
exit;
