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
// Optional - a client with just one engagement doesn't need one; a client
// running several engagements under different products (e.g. LivePerson:
// Conversation Cloud, Tenfold, Voicebase) uses this to tell them apart.
// Null/blank means "just use the client name" everywhere it's displayed -
// see includes/engagement_helpers.php.
$engagement_name = trim($_POST['engagement_name'] ?? '');
$budget_hours  = $_POST['budget_hours'] ?? null;
$status        = $_POST['status'] ?? null;
$year          = $_POST['year'] ?? date('Y');
$manager       = $_POST['manager'] ?? null;
$location      = trim($_POST['location'] ?? '');
$poc           = trim($_POST['poc'] ?? '');
$scope         = trim($_POST['scope'] ?? '');
$repeat_flag   = !empty($_POST['repeat_flag']) ? 1 : 0;
$notes         = trim($_POST['notes'] ?? '');
$soc_type      = trim($_POST['soc_type'] ?? '');
$as_of_date    = trim($_POST['as_of_date'] ?? '');
$review_period_start = trim($_POST['review_period_start'] ?? '');
$review_period_end   = trim($_POST['review_period_end'] ?? '');
// PCI Delivery Date - only relevant when PCI is one of the audit types
// (see add_engagement_modal.js's syncPciVisibility()). Assessment type
// itself is read further down alongside TSC - both are checkbox arrays.
$pci_delivery_date = trim($_POST['pci_delivery_date'] ?? '');

if (!$client_id || !$client_name || !$budget_hours || !$status || !$manager) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Insert into engagements including manager (as string)
$engagementNameValue = $engagement_name !== '' ? $engagement_name : null;
$stmt = $conn->prepare("
    INSERT INTO engagements (client_id, client_name, engagement_name, budgeted_hours, status, year, manager, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('isssssss', $client_id, $client_name, $engagementNameValue, $budget_hours, $status, $year, $manager, $notes);

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

    // PCI Assessment Type - not every PCI engagement produces just a ROC
    // (Report on Compliance); a smaller merchant/service provider might
    // file an AOC and a SAQ variant at once, so this allows more than one,
    // same "checkbox array joined with commas" approach as TSC above.
    $pciAssessmentType = implode(', ', array_filter(array_map('trim', $_POST['pci_assessment_type'] ?? [])));

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
    // Report/SOC Type - Type 1 asks for a single As-of Date, Type 2 asks
    // for a Review Period range (see add_engagement_modal.js's
    // syncReportTypeFields()). Whichever the client didn't fill in for the
    // selected type is just left null rather than guessed at.
    $socTypeValue = $soc_type !== '' ? $soc_type : null;
    $asOfDateValue = $as_of_date !== '' ? $as_of_date : null;
    $reviewStartValue = $review_period_start !== '' ? $review_period_start : null;
    $reviewEndValue = $review_period_end !== '' ? $review_period_end : null;
    $pciAssessmentTypeValue = $pciAssessmentType !== '' ? $pciAssessmentType : null;
    $pciDeliveryDateValue = $pci_delivery_date !== '' ? $pci_delivery_date : null;

    $detailsStmt = $conn->prepare("
        INSERT INTO audit_engagement_details
            (engagement_id, location, poc, tsc, scope, repeat_flag, soc_type, as_of_date, review_period_start, review_period_end, pci_assessment_type, pci_delivery_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            location = VALUES(location), poc = VALUES(poc), tsc = VALUES(tsc), scope = VALUES(scope), repeat_flag = VALUES(repeat_flag),
            soc_type = VALUES(soc_type), as_of_date = VALUES(as_of_date), review_period_start = VALUES(review_period_start), review_period_end = VALUES(review_period_end),
            pci_assessment_type = VALUES(pci_assessment_type), pci_delivery_date = VALUES(pci_delivery_date)
    ");
    $detailsStmt->bind_param(
        'issssissssss',
        $engagement_id, $locationValue, $pocValue, $tscValue, $scopeValue, $repeat_flag,
        $socTypeValue, $asOfDateValue, $reviewStartValue, $reviewEndValue, $pciAssessmentTypeValue, $pciDeliveryDateValue
    );
    $detailsStmt->execute();
    $detailsStmt->close();

    $timelineStmt = $conn->prepare("INSERT IGNORE INTO audit_engagement_timeline (engagement_id) VALUES (?)");
    $timelineStmt->bind_param('i', $engagement_id);
    $timelineStmt->execute();
    $timelineStmt->close();

    echo json_encode(['success' => true, 'engagement_id' => $engagement_id]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
    $stmt->close();
}

$conn->close();
?>
