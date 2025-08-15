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
$result = $conn->query("SELECT engagement_id, client_name FROM engagements ORDER BY client_name ASC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
}

echo json_encode($clients);
