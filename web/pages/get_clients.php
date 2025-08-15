<?php
require_once '../includes/db.php'; // adjust path if needed
session_start();

// Only admins can fetch clients
$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$clients = [];

// Fetch active clients with their status
$result = $conn->query("SELECT engagement_id, client_name, status FROM engagements ORDER BY client_name ASC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Ensure a default status if null
        if (empty($row['status'])) {
            $row['status'] = 'confirmed';
        }
        $clients[] = $row;
    }
}

echo json_encode($clients);
