<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing employee ID']);
    exit;
}

$employeeId = (int)$_GET['id'];
$today = date('Y-m-d');

// 1. Get employee details
$stmt = $conn->prepare("SELECT first_name, last_name, role FROM users WHERE user_id = ?");
$stmt->bind_param('i', $employeeId);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows === 0) {
    echo json_encode(['error' => 'Employee not found']);
    exit;
}
$user = $userResult->fetch_assoc();

// 2. Get future assignments with engagement name and assigned hours
$assignmentQuery = "
    SELECT e.client_name, a.assigned_hours, a.reporting_start
    FROM assignments a
    JOIN engagements e ON a.engagement_id = e.engagement_id
    WHERE a.user_id = ?
      AND a.reporting_start >= ?
    ORDER BY a.reporting_start ASC
";
$stmt = $conn->prepare($assignmentQuery);
$stmt->bind_param('is', $employeeId, $today);
$stmt->execute();
$assignmentResult = $stmt->get_result();

$assignments = [];
while ($row = $assignmentResult->fetch_assoc()) {
    $assignments[] = [
        'client_name' => $row['client_name'],
        'assigned_hours' => $row['assigned_hours'],
        'reporting_start' => $row['reporting_start']
    ];
}

// 3. Return all data
echo json_encode([
    'full_name' => $user['first_name'] . ' ' . $user['last_name'],
    'role' => $user['role'],
    'assignments' => $assignments
]);
