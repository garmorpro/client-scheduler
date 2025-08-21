<?php
require_once '../includes/db.php'; // adjust path as needed
session_start();

// Only allow admin users to delete entries
if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Read POSTed JSON
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['timeoff_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing timeoff_id']);
    exit();
}

$timeoff_id = intval($data['timeoff_id']);

// Prepare and execute delete
$stmt = $conn->prepare("DELETE FROM time_off WHERE timeoff_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param('i', $timeoff_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
