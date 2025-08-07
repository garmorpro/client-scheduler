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
$mondayThisWeek = date('Y-m-d', strtotime('monday this week'));
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
$stmt->bind_param('is', $employeeId, $mondayThisWeek);
$stmt->execute();
$assignmentsResult = $stmt->get_result();

$totalAssignedHours = 0;
$assignmentsByClient = [];

while ($row = $assignmentsResult->fetch_assoc()) {
    $client = htmlspecialchars($row['client_name']);
    $weekStartRaw = $row['week_start'];
    $weekStartFormatted = date('M j, Y', strtotime($weekStartRaw));
    $hours = (int)$row['assigned_hours'];
    $totalAssignedHours += $hours;

    if (!isset($assignmentsByClient[$client])) {
        $assignmentsByClient[$client] = [
            'total_hours' => 0,
            'weeks' => [],
        ];
    }

    $assignmentsByClient[$client]['total_hours'] += $hours;
    $assignmentsByClient[$client]['weeks'][] = $weekStartFormatted;
}

// Build final HTML
$assignmentItemsHTML = '';

foreach ($assignmentsByClient as $client => $data) {
    $weeksList = implode(', ', $data['weeks']);
    $hours = $data['total_hours'];

    $assignmentItemsHTML .= "
        <div class='list-group-item d-flex justify-content-between align-items-center'>
            <div>
                <strong>{$client}</strong><br />
                <small class='text-muted'>Weeks of: {$weeksList}</small>
            </div>
            <span class='badge bg-primary rounded-pill'>{$hours} hrs</span>
        </div>
    ";
}

// Optional: Calculate total available hours (adjust as needed)
$totalAvailableHours = 40 * 4; // e.g. 4 weeks * 40 hrs/week

echo json_encode([
    'full_name' => $fullName,
    'role' => $role,
    'total_assigned_hours' => $totalAssignedHours,
    'total_available_hours' => $totalAvailableHours,
    'assignment_items' => $assignmentItemsHTML
]);
