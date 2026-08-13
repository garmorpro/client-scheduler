<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/audit_timeline_fields.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$engagementId = (int) ($data['engagement_id'] ?? 0);
$column = $data['column'] ?? '';
$value = $data['value'] ?? null;

$fieldMap = audit_timeline_field_map();
if ($engagementId <= 0 || !isset($fieldMap[$column])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Date edits (moving a due date) and completion toggles are deliberately
// different permission tiers - see the migration plan's Phase 1 notes.
$type = $fieldMap[$column];
$permissionKey = $type === 'date' ? 'manage_audit_timeline' : 'complete_audit_timeline_items';

if (!audit_can_act_on_engagement($conn, $engagementId, $permissionKey)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($type === 'date') {
    $dateValue = $value !== null && $value !== '' ? $value : null;
    if ($dateValue !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
        echo json_encode(['success' => false, 'error' => 'Invalid date']);
        exit;
    }
    // $column is safe to interpolate here - it's a key looked up in the
    // hardcoded whitelist above, never the raw request value.
    $stmt = $conn->prepare("UPDATE audit_engagement_timeline SET `$column` = ? WHERE engagement_id = ?");
    $stmt->bind_param('si', $dateValue, $engagementId);
} else {
    $completed = !empty($value);
    if ($completed) {
        $stmt = $conn->prepare("UPDATE audit_engagement_timeline SET `$column` = NOW() WHERE engagement_id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE audit_engagement_timeline SET `$column` = NULL WHERE engagement_id = ?");
    }
    $stmt->bind_param('i', $engagementId);
}

$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    $userId = $_SESSION['user_id'];
    $email = $_SESSION['email'] ?? '';
    $name = $_SESSION['full_name'] ?? '';
    $eventType = $type === 'date' ? 'audit_timeline_date_updated' : 'audit_timeline_item_' . (empty($value) ? 'reopened' : 'completed');
    $title = $type === 'date' ? 'Timeline Date Updated' : (empty($value) ? 'Timeline Item Reopened' : 'Timeline Item Completed');
    $description = "Engagement #{$engagementId}: {$column}";
    $logStmt = $conn->prepare("INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)");
    if ($logStmt) {
        $logStmt->bind_param('sissss', $eventType, $userId, $email, $name, $title, $description);
        $logStmt->execute();
        $logStmt->close();
    }
}

echo json_encode(['success' => $ok]);
$conn->close();
