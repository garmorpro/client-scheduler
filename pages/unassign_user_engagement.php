<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
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
$user_id = intval($data['user_id'] ?? 0);
$engagement_id = intval($data['engagement_id'] ?? 0);

if (!$user_id || !$engagement_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid user or engagement ID']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM entries WHERE user_id = ? AND engagement_id = ?");
$stmt->bind_param("ii", $user_id, $engagement_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'removed' => $stmt->affected_rows]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
