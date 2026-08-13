<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!user_has_permission($conn, 'manage_clients_engagements')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Validate POST
$client_id     = $_POST['client_id'] ?? null;
$client_name   = $_POST['client_name'] ?? null;
$budget_hours  = $_POST['budget_hours'] ?? null;
$status        = $_POST['status'] ?? null;
$year          = $_POST['year'] ?? date('Y');
$manager       = $_POST['manager'] ?? null;
$location      = trim($_POST['location'] ?? '');
$poc           = trim($_POST['poc'] ?? '');
$scope         = trim($_POST['scope'] ?? '');
$repeat_flag   = !empty($_POST['repeat_flag']) ? 1 : 0;
$notes         = trim($_POST['notes'] ?? '');

if (!$client_id || !$client_name || !$budget_hours || !$status || !$manager) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Insert into engagements including manager (as string)
$stmt = $conn->prepare("
    INSERT INTO engagements (client_id, client_name, budgeted_hours, status, year, manager, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('issssss', $client_id, $client_name, $budget_hours, $status, $year, $manager, $notes);

if ($stmt->execute()) {
    $engagement_id = $stmt->insert_id;
    $stmt->close();

    // Audit types are optional - an engagement can have none, one, or several.
    $auditTypeIds = array_unique(array_map('intval', $_POST['audit_type_ids'] ?? []));
    if (!empty($auditTypeIds)) {
        $eatStmt = $conn->prepare("INSERT INTO engagement_audit_types (engagement_id, audit_type_id) VALUES (?, ?)");
        foreach ($auditTypeIds as $atId) {
            if ($atId <= 0) continue;
            $eatStmt->bind_param('ii', $engagement_id, $atId);
            $eatStmt->execute();
        }
        $eatStmt->close();
    }

    // TSC (SOC 2 Trust Services Criteria) - only relevant when SOC 2 is one
    // of the audit types, but harmless to store regardless of what's
    // checked. Set at creation time (not left for a Senior to fill in
    // later) since Master Schedule/DOL Generator both need audit types
    // (and TSC, for DOL's criteria derivation) to exist from day one.
    $tsc = implode(', ', array_filter(array_map('trim', $_POST['tsc'] ?? [])));
    $tscValue = $tsc !== '' ? $tsc : null;

    // Always create the paired audit_engagement_details/audit_engagement_
    // timeline rows, not just when TSC happens to be set - the View
    // Engagement panel's Details card and Timeline & Key Dates card were
    // silently missing entirely (not just empty) on any engagement that
    // skipped this insert, since renderTimelineSection() only renders at
    // all when the backend found a timeline row. A bare row (every date
    // NULL) still shows the card with "Not set" placeholders, ready for
    // Edit Timeline to fill in - same as every migrated engagement already
    // has from the one-time backfill.
    // Location/POC ported over from the Engagement Tracker reference's own
    // Create New Engagement modal - Client Scheduler's schema already had
    // columns for both (audit_engagement_details.location/poc), just
    // nothing on this form ever wrote to them until now.
    $locationValue = $location !== '' ? $location : null;
    $pocValue = $poc !== '' ? $poc : null;
    $scopeValue = $scope !== '' ? $scope : null;

    $detailsStmt = $conn->prepare("
        INSERT INTO audit_engagement_details (engagement_id, location, poc, tsc, scope, repeat_flag) VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE location = VALUES(location), poc = VALUES(poc), tsc = VALUES(tsc), scope = VALUES(scope), repeat_flag = VALUES(repeat_flag)
    ");
    $detailsStmt->bind_param('issssi', $engagement_id, $locationValue, $pocValue, $tscValue, $scopeValue, $repeat_flag);
    $detailsStmt->execute();
    $detailsStmt->close();

    $timelineStmt = $conn->prepare("INSERT IGNORE INTO audit_engagement_timeline (engagement_id) VALUES (?)");
    $timelineStmt->bind_param('i', $engagement_id);
    $timelineStmt->execute();
    $timelineStmt->close();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
    $stmt->close();
}

$conn->close();
?>
