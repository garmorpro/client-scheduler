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

// Fetch active clients with their status using JOIN to get client_name from clients table
$query = "
    SELECT client_id, client_name, status
    FROM clients;
";


$result = $conn->query($query);

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
