<?php
require_once '../includes/db.php'; // adjust path if needed
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_master_schedule')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

$user_id = intval($input['user_id'] ?? 0);
$week_start = $input['week_start'] ?? null;
$client_name = trim($input['client_name'] ?? '');
$assigned_hours = floatval($input['assigned_hours'] ?? 0);
$engagement_id = intval($input['engagement_id'] ?? 0);
$audit_type_id = isset($input['audit_type_id']) && $input['audit_type_id'] !== '' ? intval($input['audit_type_id']) : null;

if (!$user_id || !$week_start || !$client_name || $assigned_hours <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing or invalid parameters']);
    exit();
}

if ($engagement_id > 0) {
    // Trust the engagement_id the client resolved via autocomplete - this is
    // the only way to know exactly which engagement was meant when a client
    // has more than one (client_name alone is ambiguous in that case).
    $stmt = $conn->prepare("SELECT engagement_id FROM engagements WHERE engagement_id = ? AND client_name = ? LIMIT 1");
    $stmt->bind_param('is', $engagement_id, $client_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!($result && $row = $result->fetch_assoc())) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Client not found']);
        exit();
    }
    $stmt->close();
} else {
    // Freehand text with no autocomplete selection - fall back to the old
    // best-effort lookup. Ambiguous if this client has multiple engagements.
    $stmt = $conn->prepare("SELECT engagement_id FROM engagements WHERE client_name = ? LIMIT 1");
    $stmt->bind_param('s', $client_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $engagement_id = $row['engagement_id'];
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Client not found']);
        exit();
    }
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
        echo json_encode(['success' => false, 'error' => 'That audit type is not selected for this engagement.']);
        exit();
    }
}

// Insert new entry
$stmt = $conn->prepare("INSERT INTO entries (user_id, week_start, engagement_id, audit_type_id, assigned_hours) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('isiid', $user_id, $week_start, $engagement_id, $audit_type_id, $assigned_hours);

if ($stmt->execute()) {
    $entry_id = $stmt->insert_id;
    echo json_encode(['success' => true, 'entry_id' => $entry_id]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to insert entry']);
}

$stmt->close();
$conn->close();
