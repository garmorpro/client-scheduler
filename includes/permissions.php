<?php
// Central permission check, backed by the role_permissions table.
// Admins always pass regardless of the table's contents so an admin can
// never lock themselves out while editing the permissions matrix.
// service_account keeps its pre-existing carve-out for user management
// (used by update_role.php) since it isn't a role editable in the matrix.
function user_has_permission($conn, $permissionKey) {
    static $cache = [];
    $allowed = ['manage_employees', 'manage_clients_engagements', 'approve_time_off', 'access_system_settings'];
    if (!in_array($permissionKey, $allowed, true)) return false;

    $role = strtolower($_SESSION['user_role'] ?? '');
    if ($role === '') return false;
    if ($role === 'admin') return true;
    if ($role === 'service_account') return $permissionKey === 'manage_employees';

    if (!isset($cache[$role])) {
        $stmt = $conn->prepare("SELECT manage_employees, manage_clients_engagements, approve_time_off, access_system_settings FROM role_permissions WHERE role = ?");
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $cache[$role] = $row ?: [
            'manage_employees' => 0,
            'manage_clients_engagements' => 0,
            'approve_time_off' => 0,
            'access_system_settings' => 0,
        ];
    }

    return !empty($cache[$role][$permissionKey]);
}
