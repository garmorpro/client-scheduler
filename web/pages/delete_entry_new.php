<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$entry_id = intval($data['entry_id'] ?? 0);

if (!$entry_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid entry ID']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM entries WHERE entry_id = ?");
$stmt->bind_param("i", $entry_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
