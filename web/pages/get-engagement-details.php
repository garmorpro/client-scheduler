<?php
require_once 'db.php'; // Your DB connection

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);
$engagement_id = $data['engagement_id'];
$status = $data['status'];

// Prepare the SQL query to update the status
$query = "UPDATE engagements SET status = ? WHERE engagement_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param('si', $status, $engagement_id);

// Execute the query
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update status']);
}
?>
