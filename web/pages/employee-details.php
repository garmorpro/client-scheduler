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

// Fetch employee details
$userQuery = "SELECT first_name, last_name, role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('i', $employeeId);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows === 0) {
    echo json_encode(['error' => 'Employee not found']);
    exit;
}

$user = $userResult->fetch_assoc();
$fullName = $user['first_name'] . ' ' . $user['last_name'];
$role = $user['role'] ?? 'N/A';

// Fetch upcoming assignments (week_start >= today)
$today = date('Y-m-d');
$assignmentsQuery = "
    SELECT 
        e.client_name,
        a.week_start,
        a.assigned_hours
    FROM assignments a
    JOIN engagements e ON a.engagement_id = e.engagement_id
    WHERE a.user_id = ? AND a.week_start >= ?
    ORDER BY a.week_start ASC
";

$stmt = $conn->prepare($assignmentsQuery);
$stmt->bind_param('is', $employeeId, $today);
$stmt->execute();
$assignmentsResult = $stmt->get_result();

$totalAssignedHours = 0;
$assignmentItemsHTML = '';

while ($row = $assignmentsResult->fetch_assoc()) {
    $client = htmlspecialchars($row['client_name']);
    $weekStart = date('M j, Y', strtotime($row['week_start']));
    $hours = (int)$row['assigned_hours'];
    $totalAssignedHours += $hours;

    $assignmentItemsHTML .= "
        <div class='list-group-item d-flex justify-content-between align-items-center'>
            <div>
                <strong>{$client}</strong><br />
                <small class='text-muted'>Week of {$weekStart}</small>
            </div>
            <span class='badge bg-primary rounded-pill'>{$hours} hrs</span>
        </div>
    ";
}

// Optional: You can hardcode or calculate this from elsewhere if needed
$totalAvailableHours = 40 * 4; // 4 weeks * 40 hrs/week (example)

echo json_encode([
    'full_name' => $fullName,
    'role' => $role,
    'total_assigned_hours' => $totalAssignedHours,
    'total_available_hours' => $totalAvailableHours,
    'assignment_items' => $assignmentItemsHTML
]);
