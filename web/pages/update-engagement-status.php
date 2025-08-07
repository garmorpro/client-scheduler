<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw POST data
    $data = json_decode(file_get_contents("php://input"), true);

    // Debug: Log raw input for inspection
    error_log('Received Data: ' . print_r($data, true));

    // Check if engagement_id and status are set
    $engagementId = $data['engagement_id'] ?? null;
    $newStatus = $data['status'] ?? null;

    // If data is missing, log the error and return a 400 response
    if (!$engagementId || !$newStatus) {
        error_log("Missing engagement_id or status");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing engagement_id or status']);
        exit();
    }

    // Prepare SQL query to update the status
    $stmt = $conn->prepare("UPDATE engagements SET status = ? WHERE engagement_id = ?");
    $stmt->bind_param("si", $newStatus, $engagementId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        // Log the error in case the update fails
        error_log("DB update failed: " . $stmt->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB update failed']);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
