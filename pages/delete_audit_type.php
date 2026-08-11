<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'access_system_settings')) {
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
$id = intval($data['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid audit type']);
    exit();
}

// Only allow a hard delete once nothing references it - same guard pattern
// as delete_user.php. Otherwise this is a "deactivate" job, not a delete.
$stmt = $conn->prepare("
    SELECT
        (SELECT COUNT(*) FROM engagement_audit_types WHERE audit_type_id = ?) AS eng_count,
        (SELECT COUNT(*) FROM entries WHERE audit_type_id = ?) AS entry_count
");
$stmt->bind_param('ii', $id, $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row && ((int)$row['eng_count'] > 0 || (int)$row['entry_count'] > 0)) {
    echo json_encode(['success' => false, 'error' => 'This audit type is used by one or more engagements or schedule entries and can\'t be deleted. Deactivate it instead to hide it from new selections.']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM audit_types WHERE audit_type_id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Could not delete audit type.']);
}
$stmt->close();
