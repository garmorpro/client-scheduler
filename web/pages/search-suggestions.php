<?php
require_once '../includes/db.php';

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (strlen($term) < 3) {
    echo json_encode([]);
    exit();
}

$term = "%{$term}%";

$query = "
    SELECT 'employee' AS type, CONCAT(first_name, ' ', last_name) AS name
    FROM users
    WHERE status = 'active' AND CONCAT(first_name, ' ', last_name) LIKE ?

    UNION

    SELECT 'client' AS type, client_name AS name
    FROM engagements
    WHERE status = 'active' AND client_name LIKE ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $term, $term);
$stmt->execute();
$result = $stmt->get_result();

$results = [];
while ($row = $result->fetch_assoc()) {
    $results[] = $row;
}

echo json_encode($results);
?>
