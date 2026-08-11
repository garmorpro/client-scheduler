<?php
require_once '../includes/db.php'; // adjust path if needed
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_master_schedule')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// One row per engagement, each carrying its own audit types (if any) so the
// Master Schedule "add staff" flow can offer the right audit type choices
// once a specific engagement is picked, without a second round trip.
$result = $conn->query("
    SELECT e.engagement_id, e.client_name, e.status,
        at.audit_type_id, at.name AS audit_type_name, at.color AS audit_type_color
    FROM engagements e
    LEFT JOIN engagement_audit_types eat ON eat.engagement_id = e.engagement_id
    LEFT JOIN audit_types at ON at.audit_type_id = eat.audit_type_id AND at.is_active = 1
    ORDER BY e.client_name ASC
");

$clients = [];
$indexByEngagement = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $engagementId = $row['engagement_id'];
        if (!isset($indexByEngagement[$engagementId])) {
            $indexByEngagement[$engagementId] = count($clients);
            $clients[] = [
                'engagement_id' => (int) $engagementId,
                'client_name' => $row['client_name'],
                'status' => $row['status'] ?: 'confirmed',
                'audit_types' => [],
            ];
        }
        if ($row['audit_type_id']) {
            $clients[$indexByEngagement[$engagementId]]['audit_types'][] = [
                'id' => (int) $row['audit_type_id'],
                'name' => $row['audit_type_name'],
                'color' => $row['audit_type_color'],
            ];
        }
    }
}

echo json_encode($clients);
