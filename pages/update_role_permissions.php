<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json');

// Deliberately hardcoded to admin, never routed through user_has_permission() -
// editing the permissions matrix itself must never be delegable, or an admin
// could accidentally revoke their own ability to fix a bad configuration.
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$editableRoles = ['manager', 'senior', 'staff', 'intern', 'crm_team'];
$permissionKeys = ['manage_employees', 'view_employees', 'manage_clients_engagements', 'view_clients_engagements', 'view_master_schedule', 'manage_master_schedule', 'approve_time_off', 'view_time_off_requests', 'access_system_settings'];

$input = json_decode(file_get_contents('php://input'), true);
$permissions = $input['permissions'] ?? [];

if (!is_array($permissions)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("
        UPDATE role_permissions
        SET manage_employees = ?, view_employees = ?, manage_clients_engagements = ?, view_clients_engagements = ?, view_master_schedule = ?, manage_master_schedule = ?, approve_time_off = ?, view_time_off_requests = ?, access_system_settings = ?
        WHERE role = ?
    ");
    foreach ($permissions as $entry) {
        $role = strtolower(trim($entry['role'] ?? ''));
        if (!in_array($role, $editableRoles, true)) continue;

        $values = [];
        foreach ($permissionKeys as $key) {
            $values[] = !empty($entry[$key]) ? 1 : 0;
        }
        $stmt->bind_param('iiiiiiiiis', $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6], $values[7], $values[8], $role);
        $stmt->execute();
    }
    $stmt->close();

    $conn->commit();

    $adminUserId = $_SESSION['user_id'] ?? null;
    $adminEmail  = $_SESSION['email'] ?? '';
    $adminName   = $_SESSION['full_name'] ?? '';
    $logStmt = $conn->prepare("INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)");
    if ($logStmt) {
        $eventType = 'role_permissions_updated';
        $title = 'Role Permissions Updated';
        $description = 'Updated the role permissions matrix';
        $logStmt->bind_param('sissss', $eventType, $adminUserId, $adminEmail, $adminName, $title, $description);
        $logStmt->execute();
        $logStmt->close();
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Could not save permissions.']);
}

$conn->close();
