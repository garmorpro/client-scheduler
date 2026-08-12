<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$permissionKeys = [
    'manage_employees', 'view_employees',
    'manage_clients_engagements', 'view_clients_engagements',
    'view_master_schedule', 'manage_master_schedule',
    'view_my_schedule',
    'approve_time_off', 'view_time_off_requests',
    'access_system_settings',
    'manage_dol', 'view_dol',
    'manage_audit_timeline', 'complete_audit_timeline_items', 'view_audit_timeline',
];

$res = $conn->query("SELECT role, " . implode(', ', $permissionKeys) . " FROM role_permissions ORDER BY FIELD(role, 'manager','senior','staff','intern','crm_team')");
$permissions = [];
while ($row = $res->fetch_assoc()) {
    $entry = ['role' => $row['role']];
    foreach ($permissionKeys as $key) {
        $entry[$key] = (bool) $row[$key];
    }
    $permissions[] = $entry;
}
$conn->close();

echo json_encode(['permissions' => $permissions]);
