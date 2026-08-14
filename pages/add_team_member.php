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
$auditTypeId = isset($data['audit_type_id']) && $data['audit_type_id'] !== '' ? (int) $data['audit_type_id'] : null;

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

if ($auditTypeId) {
    $atStmt = $conn->prepare("SELECT 1 FROM engagement_audit_types WHERE engagement_id = ? AND audit_type_id = ?");
    $atStmt->bind_param('ii', $engagementId, $auditTypeId);
    $atStmt->execute();
    if (!$atStmt->get_result()->fetch_row()) {
        echo json_encode(['success' => false, 'error' => 'That audit type is not selected for this engagement.']);
        exit();
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
// week regardless, so which week this lands in doesn't matter.
$weekStart = date('Y-m-d', strtotime('monday this week'));
$stmt = $conn->prepare("INSERT INTO entries (user_id, week_start, engagement_id, audit_type_id, assigned_hours) VALUES (?, ?, ?, ?, 0)");
$stmt->bind_param('isii', $userId, $weekStart, $engagementId, $auditTypeId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'entry_id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add team member']);
}
$stmt->close();
$conn->close();
