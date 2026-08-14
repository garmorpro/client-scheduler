<?php
// Stages someone on an engagement with no hours logged yet - a genuine
// 0-hour entries row, same mechanism Master Schedule's "+" cell uses to
// staff someone, just with assigned_hours = 0. Lets a person show up on
// the Team card (and become DOL-assignable, since DOL Generator's team
// list is entries-derived) without needing real hours first - e.g.
// someone promoted to manager after doing real senior/staff-level work on
// the engagement, who still needs a DOL line item for it.
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_clients_engagements')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$engagementId = (int) ($data['engagement_id'] ?? 0);
$userId = (int) ($data['user_id'] ?? 0);
// Someone can be staffed under more than one of the engagement's audit
// types at once (same as staffing them via Master Schedule's own "+"
// cell), so this is a list, not a single value - one 0-hour entries row
// gets created per type. Empty means "not specific to one type" (a single
// untagged row), same as leaving audit_type blank anywhere else in the app.
$auditTypeIds = array_values(array_unique(array_filter(array_map('intval', $data['audit_type_ids'] ?? []), fn($id) => $id > 0)));

if (!$engagementId || !$userId) {
    echo json_encode(['success' => false, 'error' => 'Missing engagement or employee']);
    exit();
}

$engStmt = $conn->prepare("SELECT engagement_id FROM engagements WHERE engagement_id = ?");
$engStmt->bind_param('i', $engagementId);
$engStmt->execute();
if (!$engStmt->get_result()->fetch_row()) {
    echo json_encode(['success' => false, 'error' => 'Engagement not found']);
    exit();
}
$engStmt->close();

$userStmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND status = 'active'");
$userStmt->bind_param('i', $userId);
$userStmt->execute();
if (!$userStmt->get_result()->fetch_row()) {
    echo json_encode(['success' => false, 'error' => 'Employee not found']);
    exit();
}
$userStmt->close();

if (!empty($auditTypeIds)) {
    $atStmt = $conn->prepare("SELECT 1 FROM engagement_audit_types WHERE engagement_id = ? AND audit_type_id = ?");
    foreach ($auditTypeIds as $atId) {
        $atStmt->bind_param('ii', $engagementId, $atId);
        $atStmt->execute();
        if (!$atStmt->get_result()->fetch_row()) {
            echo json_encode(['success' => false, 'error' => 'One of the selected audit types is not selected for this engagement.']);
            exit();
        }
    }
    $atStmt->close();
}

// Already staffed (any week, any audit type)? Don't create a redundant
// second placeholder row.
$existsStmt = $conn->prepare("SELECT 1 FROM entries WHERE engagement_id = ? AND user_id = ? LIMIT 1");
$existsStmt->bind_param('ii', $engagementId, $userId);
$existsStmt->execute();
if ($existsStmt->get_result()->fetch_row()) {
    echo json_encode(['success' => false, 'error' => 'This person is already on the team.']);
    exit();
}
$existsStmt->close();

// Week is just a placeholder (current week) - Team card, DOL Generator, and
// everything else that reads staffing from entries sums hours across every
// week regardless, so which week this lands in doesn't matter. One row per
// selected audit type (or a single untagged row when none were picked).
$weekStart = date('Y-m-d', strtotime('monday this week'));
$stmt = $conn->prepare("INSERT INTO entries (user_id, week_start, engagement_id, audit_type_id, assigned_hours) VALUES (?, ?, ?, ?, 0)");

$rowsToInsert = !empty($auditTypeIds) ? $auditTypeIds : [null];
$insertedIds = [];
$ok = true;
foreach ($rowsToInsert as $atId) {
    $stmt->bind_param('isii', $userId, $weekStart, $engagementId, $atId);
    if ($stmt->execute()) {
        $insertedIds[] = $stmt->insert_id;
    } else {
        $ok = false;
        break;
    }
}
$stmt->close();

if ($ok) {
    echo json_encode(['success' => true, 'entry_ids' => $insertedIds]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add team member']);
}
$conn->close();
