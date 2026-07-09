<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (isset($_GET['query'])) {
    $query = '%' . $_GET['query'] . '%';

    // Search for clients (engagements)
    $clientQuery = "SELECT engagement_id AS id, client_name AS name, 'client' AS type FROM engagements WHERE client_name LIKE ?";
    $stmt = $conn->prepare($clientQuery);
    $stmt->bind_param('s', $query);
    $stmt->execute();
    $clientResult = $stmt->get_result();
    $clients = [];
    while ($row = $clientResult->fetch_assoc()) {
        $clients[] = $row;
    }

    // Check if clients (engagements) were found
    if (count($clients) > 0) {
        // Debugging log for found engagements
        // echo 'Found engagements!';
    } else {
        // Debugging log for no engagements found
        // echo 'No engagements found';
    }

    // Search for employees
    $employeeQuery = "SELECT user_id AS id, full_name AS name, 'employee' AS type FROM users WHERE full_name LIKE ? AND status = 'active'";
    $stmt = $conn->prepare($employeeQuery);
    $stmt->bind_param('s', $query);
    $stmt->execute();
    $employeeResult = $stmt->get_result();
    $employees = [];
    while ($row = $employeeResult->fetch_assoc()) {
        $employees[] = $row;
    }

    // Combine both results
    $results = array_merge($clients, $employees);

    // Return results as JSON
    echo json_encode($results);
}
?>
