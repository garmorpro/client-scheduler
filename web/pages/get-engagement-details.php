<?php
require_once '../includes/db.php';

// Get the engagement ID from the URL
$engagementId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($engagementId > 0) {
    $query = "SELECT client_name, status, total_assigned_hours, total_available_hours FROM engagements WHERE engagement_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $engagementId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Engagement not found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid engagement ID']);
}
?>
