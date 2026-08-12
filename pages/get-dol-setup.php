<?php
// Everything the DOL Generator needs for one engagement: which DOL-capable
// audit types it covers, its team (senior/staff/intern only - managers
// don't get DOL line items), each person's hours on this engagement (real
// data from entries, summed across all their rows regardless of which
// audit_type_id each is tagged with - just a starting point, still
// editable in the generator), and training restrictions per person.
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_dol')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$engagementId = (int) ($_GET['engagement_id'] ?? 0);
if (!$engagementId) {
    echo json_encode(['success' => false, 'error' => 'Missing engagement_id']);
    exit();
}

$stmt = $conn->prepare("
    SELECT e.engagement_id, e.client_name, e.year, d.tsc
    FROM engagements e
    LEFT JOIN audit_engagement_details d ON d.engagement_id = e.engagement_id
    WHERE e.engagement_id = ?
");
$stmt->bind_param('i', $engagementId);
$stmt->execute();
$engagement = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$engagement) {
    echo json_encode(['success' => false, 'error' => 'Engagement not found']);
    exit();
}

// Only the 5 frameworks the DOL Generator actually knows how to split.
$dolAuditTypeNames = ['SOC 1', 'SOC 2', 'HIPAA', 'HITRUST', 'FISMA'];
$placeholders = implode(',', array_fill(0, count($dolAuditTypeNames), '?'));
$types = str_repeat('s', count($dolAuditTypeNames));
$stmt = $conn->prepare("
    SELECT at.audit_type_id, at.name
    FROM engagement_audit_types eat
    JOIN audit_types at ON at.audit_type_id = eat.audit_type_id
    WHERE eat.engagement_id = ? AND at.name IN ($placeholders)
    ORDER BY at.name
");
// engagement_id is the first ? in the query, so it binds first.
$bindArgs = ['i' . $types, $engagementId, ...$dolAuditTypeNames];
$refs = [$bindArgs[0]];
for ($i = 1; $i < count($bindArgs); $i++) {
    $refs[] = &$bindArgs[$i];
}
call_user_func_array([$stmt, 'bind_param'], $refs);
$stmt->execute();
$auditTypes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Team = anyone with at least one entries row for this engagement (Phase 0's
// "team membership derives from entries" decision), role senior/staff/intern.
$stmt = $conn->prepare("
    SELECT u.user_id, u.full_name, u.role, COALESCE(SUM(en.assigned_hours), 0) AS hours
    FROM entries en
    JOIN users u ON u.user_id = en.user_id
    WHERE en.engagement_id = ? AND u.role IN ('senior', 'staff', 'intern')
    GROUP BY u.user_id, u.full_name, u.role
    ORDER BY FIELD(u.role, 'senior', 'staff', 'intern'), u.full_name
");
$stmt->bind_param('i', $engagementId);
$stmt->execute();
$team = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$restrictions = [];
if (!empty($team)) {
    $userIds = array_column($team, 'user_id');
    $ph = implode(',', array_fill(0, count($userIds), '?'));
    $t = str_repeat('i', count($userIds));
    $stmt = $conn->prepare("SELECT user_id, criterion FROM dol_training_restrictions WHERE user_id IN ($ph)");
    $stmt->bind_param($t, ...$userIds);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $restrictions[$row['user_id']][] = $row['criterion'];
    }
    $stmt->close();
}

echo json_encode([
    'success' => true,
    'engagement' => [
        'engagement_id' => (int) $engagement['engagement_id'],
        'client_name' => $engagement['client_name'],
        'year' => (int) $engagement['year'],
        'tsc' => $engagement['tsc'],
    ],
    'audit_types' => array_map(fn($a) => ['audit_type_id' => (int) $a['audit_type_id'], 'name' => $a['name']], $auditTypes),
    'team' => array_map(fn($m) => [
        'user_id' => (int) $m['user_id'],
        'full_name' => $m['full_name'],
        'role' => $m['role'],
        'hours' => (float) $m['hours'],
        'restricted' => $restrictions[$m['user_id']] ?? [],
    ], $team),
]);
