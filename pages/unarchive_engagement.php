<?php
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

$data = json_decode(file_get_contents('php://input'), true);
$history_id = intval($data['history_id'] ?? 0);

if (!$history_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid history ID']);
    exit;
}

$stmt = $conn->prepare("
    SELECT h.client_id, h.budgeted_hours, h.manager, h.notes, c.client_name
    FROM client_engagement_history h
    JOIN clients c ON h.client_id = c.client_id
    WHERE h.history_id = ?
");
$stmt->bind_param('i', $history_id);
$stmt->execute();
$history = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$history) {
    echo json_encode(['success' => false, 'message' => 'Archived record not found']);
    exit;
}

// Restored engagements land as "pending" - the original status wasn't
// preserved when archived, so it needs to be reconfirmed.
$status = 'pending';
$year = date('Y');

$insert = $conn->prepare("
    INSERT INTO engagements (client_id, client_name, budgeted_hours, status, year, manager, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$insert->bind_param(
    'isdsiss',
    $history['client_id'],
    $history['client_name'],
    $history['budgeted_hours'],
    $status,
    $year,
    $history['manager'],
    $history['notes']
);

if (!$insert->execute()) {
    echo json_encode(['success' => false, 'message' => $insert->error]);
    exit;
}
$insert->close();

$delete = $conn->prepare("DELETE FROM client_engagement_history WHERE history_id = ?");
$delete->bind_param('i', $history_id);
$delete->execute();
$delete->close();

echo json_encode(['success' => true]);
$conn->close();
