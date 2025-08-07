<?php
require_once('../db.php'); // If it's one level above


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
