<?php
require_once '../includes/db.php';

if (isset($_GET['query'])) {
    $query = '%' . $_GET['query'] . '%';

    // Search for clients
    $clientQuery = "SELECT engagement_id AS id, client_name AS name, 'client' AS type FROM engagements WHERE client_name LIKE ? AND status = 'active'";
    $stmt = $conn->prepare($clientQuery);
    $stmt->bind_param('s', $query);
    $stmt->execute();
    $clientResult = $stmt->get_result();
    $clients = [];
    while ($row = $clientResult->fetch_assoc()) {
        $clients[] = $row;
    }

    // Search for employees
    $employeeQuery = "SELECT user_id AS id, CONCAT(first_name, ' ', last_name) AS name, 'employee' AS type FROM users WHERE CONCAT(first_name, ' ', last_name) LIKE ? AND status = 'active'";
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
