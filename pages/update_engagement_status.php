<?php
// Lightweight status-only update for the View Engagement panel's inline
// status dropdown - edit_engagement.php requires the whole form (budgeted
// hours, manager, etc.) which the panel doesn't have inline.
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_clients_engagements')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$engagementId = (int) ($data['engagement_id'] ?? 0);
$status = $data['status'] ?? '';

$allowedStatuses = ['confirmed', 'pending', 'not_confirmed'];
if ($engagementId <= 0 || !in_array($status, $allowedStatuses, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$stmt = $conn->prepare("UPDATE engagements SET status = ? WHERE engagement_id = ?");
$stmt->bind_param('si', $status, $engagementId);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);
$conn->close();
