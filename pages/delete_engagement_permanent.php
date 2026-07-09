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
$engagement_id = intval($data['engagement_id'] ?? 0);

if (!$engagement_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid engagement ID']);
    exit;
}

// No history record is kept here - this is a permanent delete, distinct from
// archive_engagement.php which copies to client_engagement_history first.
$stmt = $conn->prepare("DELETE FROM entries WHERE engagement_id = ?");
$stmt->bind_param('i', $engagement_id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("DELETE FROM engagements WHERE engagement_id = ?");
$stmt->bind_param('i', $engagement_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
