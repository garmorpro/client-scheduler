<?php
// Weekly status call day/time/group are on audit_engagement_timeline but
// aren't part of the fixed date/completed column whitelist in
// audit_timeline_fields.php (they're not a "due date" - a recurring weekday
// + time + an optional cross-engagement link group), so this gets its own
// small endpoint rather than being shoehorned into update_audit_timeline_field.php.
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
$day = $data['day'] ?? null; // 0-6 (Sunday-Saturday) or null to clear
$time = $data['time'] ?? null; // "HH:MM" or null to clear
$groupName = trim($data['group_name'] ?? '');

if ($engagementId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}
if ($day !== null && $day !== '' && (!is_numeric($day) || $day < 0 || $day > 6)) {
    echo json_encode(['success' => false, 'error' => 'Invalid day']);
    exit;
}
if ($time !== null && $time !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
    echo json_encode(['success' => false, 'error' => 'Invalid time']);
    exit;
}

if (!audit_can_act_on_engagement($conn, $engagementId, 'manage_audit_timeline')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$dayValue = ($day === null || $day === '') ? null : (int) $day;
$timeValue = ($time === null || $time === '') ? null : $time . ':00';
$groupValue = $groupName !== '' ? $groupName : null;

$stmt = $conn->prepare("UPDATE audit_engagement_timeline SET weekly_status_call_day = ?, weekly_status_call_time = ?, weekly_status_call_group_name = ? WHERE engagement_id = ?");
$stmt->bind_param('issi', $dayValue, $timeValue, $groupValue, $engagementId);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);
$conn->close();
