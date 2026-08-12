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

if (!$client_id || !$client_name || !$budget_hours || !$status || !$manager) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Insert into engagements including manager (as string)
$stmt = $conn->prepare("
    INSERT INTO engagements (client_id, client_name, budgeted_hours, status, year, manager) 
    VALUES (?, ?, ?, ?, ?, ?)
");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('isssss', $client_id, $client_name, $budget_hours, $status, $year, $manager);

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
    if ($tsc !== '') {
        $tscStmt = $conn->prepare("
            INSERT INTO audit_engagement_details (engagement_id, tsc) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE tsc = VALUES(tsc)
        ");
        $tscStmt->bind_param('is', $engagement_id, $tsc);
        $tscStmt->execute();
        $tscStmt->close();
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
    $stmt->close();
}

$conn->close();
?>
