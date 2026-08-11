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

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
    $stmt->close();
}

$conn->close();
