<?php
// Every active employee, any role - feeds the "Add Team Member" picker on
// the View Engagement panel's Team card. Deliberately not role-filtered
// (unlike the Manager dropdown elsewhere): someone staffed as a senior can
// later be promoted to manager and still needs to be addable to a team
// roster, and there's no reason to assume in advance who might need to be
// added.
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_clients_engagements')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// service_account is a technical/API identity, never a real team member -
// everything else (including admin) is fair game to add.
$result = $conn->query("SELECT user_id, full_name, role FROM users WHERE status = 'active' AND role != 'service_account' ORDER BY full_name ASC");
$employees = [];
while ($result && $row = $result->fetch_assoc()) {
    $employees[] = [
        'user_id' => (int) $row['user_id'],
        'full_name' => $row['full_name'],
        'role' => $row['role'],
    ];
}

echo json_encode($employees);
