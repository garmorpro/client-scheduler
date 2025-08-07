<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if db.php exists before including it
if (!file_exists('../includes/db.php')) {
    echo json_encode(['error' => 'Database connection file not found.']);
    exit;
}

require_once '../includes/db.php';

// Ensure that the request is via GET and has the 'id' parameter
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing engagement ID']);
    exit;
}

// Get the engagement ID
$engagement_id = $_GET['id'];

// Prepare SQL query to fetch engagement details using PDO
$query = "SELECT * FROM engagements WHERE engagement_id = :engagement_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':engagement_id', $engagement_id, PDO::PARAM_INT);

// Execute the query
if ($stmt->execute()) {
    // Fetch data as associative array
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode($data);
    } else {
        // No engagement found
        echo json_encode(['error' => 'No engagement found with this ID']);
    }
} else {
    // Error executing query
    echo json_encode(['error' => 'Database query failed']);
}
?>
