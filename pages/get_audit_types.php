<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
header('Content-Type: application/json');

// Reference data only (no sensitive info) - anyone logged in can read it,
// since it's needed by both engagement management and Master Schedule
// staffing, which sit behind different permissions.
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$includeInactive = isset($_GET['include_inactive']) && $_GET['include_inactive'] === '1';

$sql = "SELECT audit_type_id, name, color, is_active FROM audit_types";
if (!$includeInactive) $sql .= " WHERE is_active = 1";
$sql .= " ORDER BY name ASC";

$result = $conn->query($sql);
$auditTypes = [];
while ($row = $result->fetch_assoc()) {
    $auditTypes[] = [
        'id' => (int) $row['audit_type_id'],
        'name' => $row['name'],
        'color' => $row['color'],
        'is_active' => (bool) $row['is_active'],
    ];
}

echo json_encode(['success' => true, 'audit_types' => $auditTypes]);
