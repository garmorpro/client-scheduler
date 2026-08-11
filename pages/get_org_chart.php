<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'view_employees')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$result = $conn->query("
    SELECT user_id, full_name, role, job_title, manager_id
    FROM users
    WHERE status = 'active'
    ORDER BY full_name ASC
");

$admins = [];
$managers = [];   // user_id => manager row
$reportsByManager = []; // manager user_id => [report, report, ...]
$unassigned = [];

$allUsers = [];
while ($row = $result->fetch_assoc()) {
    $allUsers[$row['user_id']] = $row;
}

foreach ($allUsers as $row) {
    $role = strtolower($row['role']);
    if ($role === 'admin') {
        $admins[] = $row;
    } elseif ($role === 'manager') {
        $managers[$row['user_id']] = $row;
    }
}

foreach ($allUsers as $row) {
    $role = strtolower($row['role']);
    if ($role === 'admin' || $role === 'manager') continue;

    $managerId = $row['manager_id'];
    if ($managerId && isset($managers[$managerId])) {
        $reportsByManager[$managerId][] = $row;
    } else {
        $unassigned[] = $row;
    }
}

function formatUser($row) {
    return [
        'user_id' => (int) $row['user_id'],
        'full_name' => $row['full_name'],
        'role' => strtolower($row['role']),
        'job_title' => $row['job_title'] ?? '',
    ];
}

$managerNodes = [];
foreach ($managers as $manager) {
    $reports = $reportsByManager[$manager['user_id']] ?? [];
    usort($reports, function ($a, $b) { return strcmp($a['full_name'], $b['full_name']); });
    $managerNodes[] = [
        'manager' => formatUser($manager),
        'reports' => array_map('formatUser', $reports),
    ];
}
usort($managerNodes, function ($a, $b) { return strcmp($a['manager']['full_name'], $b['manager']['full_name']); });

usort($unassigned, function ($a, $b) { return strcmp($a['full_name'], $b['full_name']); });

echo json_encode([
    'success' => true,
    'admins' => array_map('formatUser', $admins),
    'manager_nodes' => $managerNodes,
    'unassigned' => array_map('formatUser', $unassigned),
]);
