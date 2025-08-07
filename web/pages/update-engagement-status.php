<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw POST data
    $data = json_decode(file_get_contents("php://input"), true);

    // Debug: Log raw input for inspection
    error_log('Received Data: ' . print_r($data, true));

    // Check if engagement_id and status are set
    $engagementId = $data['engagement_id'] ?? null;
    $newStatus = $data['status'] ?? null;

    if (!$engagementId || !$newStatus) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing engagement_id or status']);
        exit();
    }

    // Prepare SQL query
    $stmt = $conn->prepare("UPDATE engagements SET status = ? WHERE engagement_id = ?");
    $stmt->bind_param("si", $newStatus, $engagementId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB update failed']);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
