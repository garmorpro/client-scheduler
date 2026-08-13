<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_clients_engagements')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$engagement_id = intval($_POST['engagement_id'] ?? 0);
$budgeted_hours = $_POST['budgeted_hours'] ?? null;
$status = $_POST['status'] ?? null;
$manager = $_POST['manager'] ?? null;
$notes = $_POST['notes'] ?? '';
$location = trim($_POST['location'] ?? '');
$poc = trim($_POST['poc'] ?? '');
$scope = trim($_POST['scope'] ?? '');
$repeat_flag = !empty($_POST['repeat_flag']) ? 1 : 0;
$soc_type = trim($_POST['soc_type'] ?? '');
$as_of_date = trim($_POST['as_of_date'] ?? '');
$review_period_start = trim($_POST['review_period_start'] ?? '');
$review_period_end = trim($_POST['review_period_end'] ?? '');

if (!$engagement_id || $budgeted_hours === null || !$status || !$manager) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$stmt = $conn->prepare("UPDATE engagements SET budgeted_hours = ?, status = ?, manager = ?, notes = ? WHERE engagement_id = ?");
$stmt->bind_param('ssssi', $budgeted_hours, $status, $manager, $notes, $engagement_id);

if ($stmt->execute()) {
    $stmt->close();

    // Replace the audit type selection wholesale - simpler and safer than
    // diffing, and this list is short enough that it's not worth the extra
    // complexity.
    $auditTypeIds = array_unique(array_map('intval', $_POST['audit_type_ids'] ?? []));
    $delStmt = $conn->prepare("DELETE FROM engagement_audit_types WHERE engagement_id = ?");
    $delStmt->bind_param('i', $engagement_id);
    $delStmt->execute();
    $delStmt->close();

    if (!empty($auditTypeIds)) {
        $eatStmt = $conn->prepare("INSERT INTO engagement_audit_types (engagement_id, audit_type_id) VALUES (?, ?)");
        foreach ($auditTypeIds as $atId) {
            if ($atId <= 0) continue;
            $eatStmt->bind_param('ii', $engagement_id, $atId);
            $eatStmt->execute();
        }
        $eatStmt->close();
    }

    // TSC (SOC 2 Trust Services Criteria) - always overwrite with whatever
    // was submitted (including clearing it to '' if SOC 2 got unchecked),
    // same "replace wholesale" approach as audit types above. Location/POC
    // (ported over from the Engagement Tracker reference's Create New
    // Engagement modal - add_engagement.php gained these first) ride along
    // in the same upsert.
    $tsc = implode(', ', array_filter(array_map('trim', $_POST['tsc'] ?? [])));
    // Report/SOC Type - Type 1 asks for a single As-of Date, Type 2 asks
    // for a Review Period range (see edit_engagement_modal.js's
    // syncReportTypeFields()). Empty string clears a column back to null,
    // same "always overwrite wholesale" approach as everything else here.
    $socTypeValue = $soc_type !== '' ? $soc_type : null;
    $asOfDateValue = $as_of_date !== '' ? $as_of_date : null;
    $reviewStartValue = $review_period_start !== '' ? $review_period_start : null;
    $reviewEndValue = $review_period_end !== '' ? $review_period_end : null;

    $tscStmt = $conn->prepare("
        INSERT INTO audit_engagement_details
            (engagement_id, tsc, location, poc, scope, repeat_flag, soc_type, as_of_date, review_period_start, review_period_end)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            tsc = VALUES(tsc), location = VALUES(location), poc = VALUES(poc), scope = VALUES(scope), repeat_flag = VALUES(repeat_flag),
            soc_type = VALUES(soc_type), as_of_date = VALUES(as_of_date), review_period_start = VALUES(review_period_start), review_period_end = VALUES(review_period_end)
    ");
    $tscStmt->bind_param(
        'issssissss',
        $engagement_id, $tsc, $location, $poc, $scope, $repeat_flag,
        $socTypeValue, $asOfDateValue, $reviewStartValue, $reviewEndValue
    );
    $tscStmt->execute();
    $tscStmt->close();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
    $stmt->close();
}

$conn->close();
