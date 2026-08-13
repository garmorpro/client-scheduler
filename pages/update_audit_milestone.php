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
$milestoneId = (int) ($data['milestone_id'] ?? 0);
$action = $data['action'] ?? '';
$value = $data['value'] ?? null;

if ($milestoneId <= 0 || !in_array($action, ['set_due_date', 'toggle_complete'], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$stmt = $conn->prepare("SELECT engagement_id FROM audit_engagement_milestones WHERE milestone_id = ?");
$stmt->bind_param('i', $milestoneId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Milestone not found']);
    exit;
}
$engagementId = (int) $row['engagement_id'];

$permissionKey = $action === 'set_due_date' ? 'manage_audit_timeline' : 'complete_audit_timeline_items';
if (!audit_can_act_on_engagement($conn, $engagementId, $permissionKey)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($action === 'set_due_date') {
    $dateValue = $value !== null && $value !== '' ? $value : null;
    if ($dateValue !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
        echo json_encode(['success' => false, 'error' => 'Invalid date']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE audit_engagement_milestones SET due_date = ? WHERE milestone_id = ?");
    $stmt->bind_param('si', $dateValue, $milestoneId);
} else {
    $completed = !empty($value) ? 1 : 0;
    $stmt = $conn->prepare("UPDATE audit_engagement_milestones SET is_completed = ? WHERE milestone_id = ?");
    $stmt->bind_param('ii', $completed, $milestoneId);
}

$ok = $stmt->execute();
$stmt->close();
echo json_encode(['success' => $ok]);
$conn->close();
