<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_master_schedule')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['entry_id'], $data['client_name'], $data['assigned_hours'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit();
}

$entry_id      = intval($data['entry_id']);
$client_name   = trim($data['client_name']);
$hours         = floatval($data['assigned_hours']);
$engagement_id = intval($data['engagement_id'] ?? 0);
$audit_type_id = isset($data['audit_type_id']) && $data['audit_type_id'] !== '' ? intval($data['audit_type_id']) : null;

if ($engagement_id > 0) {
    // Trust the engagement_id the client already knew (the badge being
    // edited is already tied to one specific engagement, unambiguously). No
    // longer cross-checked against client_name - see add_entry_new.php for
    // why: engagement_id alone is already authoritative, and the input
    // field can now show a combined "Client — Engagement Name" label that
    // wouldn't equal the raw client_name column even on a correct pick.
    $stmt = $conn->prepare("SELECT engagement_id FROM engagements WHERE engagement_id = ?");
    $stmt->bind_param('i', $engagement_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Client not found in engagements table']);
        exit();
    }
    $stmt->close();
} else {
    // Client name changed with no re-selected engagement - fall back to
    // best-effort lookup, same as before.
    $stmt = $conn->prepare("SELECT engagement_id FROM engagements WHERE client_name = ?");
    $stmt->bind_param('s', $client_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Client not found in engagements table']);
        exit();
    }
    $row = $result->fetch_assoc();
    $engagement_id = intval($row['engagement_id']);
    $stmt->close();
}

if ($audit_type_id !== null) {
    $atStmt = $conn->prepare("SELECT 1 FROM engagement_audit_types WHERE engagement_id = ? AND audit_type_id = ?");
    $atStmt->bind_param('ii', $engagement_id, $audit_type_id);
    $atStmt->execute();
    $isValidAuditType = (bool) $atStmt->get_result()->fetch_row();
    $atStmt->close();
    if (!$isValidAuditType) {
        http_response_code(400);
        echo json_encode(['error' => 'That audit type is not selected for this engagement.']);
        exit();
    }
}

$stmt = $conn->prepare("UPDATE entries SET engagement_id = ?, audit_type_id = ?, assigned_hours = ? WHERE entry_id = ?");
$stmt->bind_param('iidi', $engagement_id, $audit_type_id, $hours, $entry_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'engagement_id' => $engagement_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database update failed']);
}

$stmt->close();
$conn->close();
