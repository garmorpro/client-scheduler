<?php
require_once '../includes/db.php'; // adjust path if needed
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_clients_engagements')) {
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
