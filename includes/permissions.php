<?php
// Central permission check, backed by the role_permissions table.
// Admins always pass regardless of the table's contents so an admin can
// never lock themselves out while editing the permissions matrix.
// service_account keeps its pre-existing carve-out for user management
// (used by update_role.php) since it isn't a role editable in the matrix.
//
// "manage_*" implies "view_*" - a role granted edit rights can obviously
// also view that area, so the matrix only needs an explicit view_* toggle
// for roles that should see something read-only without being able to
// change it. There's no way to grant manage_* without view_* by mistake.
function user_has_permission($conn, $permissionKey) {
    static $cache = [];
    $allowed = [
        'manage_employees', 'view_employees',
        'manage_clients_engagements', 'view_clients_engagements',
        'approve_time_off', 'access_system_settings',
    ];
    if (!in_array($permissionKey, $allowed, true)) return false;

    $role = strtolower($_SESSION['user_role'] ?? '');
    if ($role === '') return false;
    if ($role === 'admin') return true;
    if ($role === 'service_account') return in_array($permissionKey, ['manage_employees', 'view_employees'], true);

    if (!isset($cache[$role])) {
        $stmt = $conn->prepare("SELECT manage_employees, view_employees, manage_clients_engagements, view_clients_engagements, approve_time_off, access_system_settings FROM role_permissions WHERE role = ?");
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $cache[$role] = $row ?: [
            'manage_employees' => 0,
            'view_employees' => 0,
            'manage_clients_engagements' => 0,
            'view_clients_engagements' => 0,
            'approve_time_off' => 0,
            'access_system_settings' => 0,
        ];
    }

    if ($permissionKey === 'view_employees') {
        return !empty($cache[$role]['view_employees']) || !empty($cache[$role]['manage_employees']);
    }
    if ($permissionKey === 'view_clients_engagements') {
        return !empty($cache[$role]['view_clients_engagements']) || !empty($cache[$role]['manage_clients_engagements']);
    }

    return !empty($cache[$role][$permissionKey]);
}
