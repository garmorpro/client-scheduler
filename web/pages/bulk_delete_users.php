<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

// Simple authentication check - you can expand with role check for admin only
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['user_ids']) || !is_array($input['user_ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

// Sanitize input user IDs as integers
$userIds = array_filter(array_map('intval', $input['user_ids']));
if (empty($userIds)) {
    echo json_encode(['success' => false, 'error' => 'No valid user IDs provided']);
    exit();
}

// Prepare placeholders for prepared statement
$placeholders = implode(',', array_fill(0, count($userIds), '?'));
$sql = "DELETE FROM users WHERE user_id IN ($placeholders)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare error: ' . $conn->error]);
    exit();
}

// Bind parameters dynamically
$types = str_repeat('i', count($userIds));
$stmt->bind_param($types, ...$userIds);

if ($stmt->execute()) {
    $deletedCount = $stmt->affected_rows;

    // Optionally: log bulk delete action here

    echo json_encode(['success' => true, 'deletedCount' => $deletedCount]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database execute error: ' . $stmt->error]);
}

$stmt->close();
?>
