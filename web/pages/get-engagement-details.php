<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if db.php exists before including it
if (!file_exists('../db.php')) {
    echo json_encode(['error' => 'Database connection file not found.']);
    exit;
}

require_once('../db.php');

// Your database query and other logic here...
?>



// Ensure that the request is via GET and has the 'id' parameter
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing engagement ID']);
    exit;
}

// Get the engagement ID
$engagement_id = $_GET['id'];

// Prepare SQL query to fetch engagement details
$query = "SELECT * FROM engagements WHERE engagement_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $engagement_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch data as associative array
    $data = $result->fetch_assoc();
    echo json_encode($data);
} else {
    // No engagement found
    echo json_encode(['error' => 'No engagement found with this ID']);
}
?>
