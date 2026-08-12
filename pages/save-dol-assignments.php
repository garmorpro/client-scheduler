<?php
// Replaces one engagement's DOL for one audit type entirely - matches
// Engagement Tracker's original save semantics exactly ("saving replaces
// this engagement's DOL for this audit type; any other audit type is
// untouched"). Delete-then-insert rather than diffing, since a full
// replace is simpler and the generator always regenerates the whole split
// anyway.
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_dol')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$engagementId = (int) ($input['engagement_id'] ?? 0);
$auditTypeId = (int) ($input['audit_type_id'] ?? 0);
$assignments = $input['assignments'] ?? []; // [{user_id, criteria: [...]}, ...]

if (!$engagementId || !$auditTypeId || !is_array($assignments)) {
    echo json_encode(['success' => false, 'error' => 'Missing or invalid request']);
    exit();
}

// Same scoping as get-dol-setup.php - a non-admin can only save DOL for an
// engagement they're personally staffed on.
$isAdmin = strtolower($_SESSION['user_role'] ?? '') === 'admin';
if (!$isAdmin) {
    $accessStmt = $conn->prepare("SELECT 1 FROM entries WHERE engagement_id = ? AND user_id = ? LIMIT 1");
    $accessStmt->bind_param('ii', $engagementId, $_SESSION['user_id']);
    $accessStmt->execute();
    $hasAccess = (bool) $accessStmt->get_result()->fetch_row();
    $accessStmt->close();
    if (!$hasAccess) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("DELETE FROM audit_dol_assignments WHERE engagement_id = ? AND audit_type_id = ?");
    $stmt->bind_param('ii', $engagementId, $auditTypeId);
    $stmt->execute();
    $stmt->close();

    $insertStmt = $conn->prepare("INSERT INTO audit_dol_assignments (engagement_id, user_id, audit_type_id, criterion) VALUES (?, ?, ?, ?)");
    $peopleCount = 0;
    foreach ($assignments as $entry) {
        $userId = (int) ($entry['user_id'] ?? 0);
        $criteria = array_filter(array_map('trim', $entry['criteria'] ?? []));
        if (!$userId) continue;
        $peopleCount++;
        foreach ($criteria as $criterion) {
            $insertStmt->bind_param('iiis', $engagementId, $userId, $auditTypeId, $criterion);
            $insertStmt->execute();
        }
    }
    $insertStmt->close();

    $conn->commit();

    $adminUserId = $_SESSION['user_id'] ?? null;
    $adminEmail = $_SESSION['email'] ?? '';
    $adminName = $_SESSION['full_name'] ?? '';
    $logStmt = $conn->prepare("INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)");
    if ($logStmt) {
        $eventType = 'dol_assignments_saved';
        $title = 'DOL Saved';
        $description = "Saved DOL split for engagement {$engagementId} (audit_type_id {$auditTypeId}) across {$peopleCount} people";
        $logStmt->bind_param('sissss', $eventType, $adminUserId, $adminEmail, $adminName, $title, $description);
        $logStmt->execute();
        $logStmt->close();
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Could not save DOL assignments.']);
}

$conn->close();
