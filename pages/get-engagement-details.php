<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/db.php';


// Check if $conn (MySQLi) is defined
if (!isset($conn)) {
    echo json_encode(['error' => 'Database connection not established.']);
    exit;
}

// Ensure that the request is via GET and has the 'id' parameter
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing engagement ID']);
    exit;
}

$engagement_id = intval($_GET['id']);

// Prepare SQL query using MySQLi
$query = "SELECT * FROM engagements WHERE engagement_id = ?";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    echo json_encode(['error' => 'Failed to prepare statement']);
    exit;
}

$stmt->bind_param("i", $engagement_id);

// Execute the query
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'No engagement found with this ID']);
    }
} else {
    echo json_encode(['error' => 'Database query failed']);
}

$stmt->close();
?>
