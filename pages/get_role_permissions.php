<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$res = $conn->query("SELECT role, manage_employees, manage_clients_engagements, approve_time_off, access_system_settings FROM role_permissions ORDER BY FIELD(role, 'manager','senior','staff','intern','crm_team')");
$permissions = [];
while ($row = $res->fetch_assoc()) {
    $permissions[] = [
        'role' => $row['role'],
        'manage_employees' => (bool) $row['manage_employees'],
        'manage_clients_engagements' => (bool) $row['manage_clients_engagements'],
        'approve_time_off' => (bool) $row['approve_time_off'],
        'access_system_settings' => (bool) $row['access_system_settings'],
    ];
}
$conn->close();

echo json_encode(['permissions' => $permissions]);
