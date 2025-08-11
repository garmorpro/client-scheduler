<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['engagement_id']) || !is_numeric($_GET['engagement_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid engagement ID']);
    exit;
}

$engagement_id = (int)$_GET['engagement_id'];

$sql = "
    SELECT 
        e.engagement_id,
        e.client_name,
        e.total_available_hours,
        e.status,
        e.notes,
        COALESCE(SUM(a.assigned_hours), 0) AS total_assigned_hours,
        COALESCE(COUNT(DISTINCT a.user_id), 0) AS assigned_user_count
    FROM engagements e
    LEFT JOIN assignments a ON e.engagement_id = a.engagement_id
    WHERE e.engagement_id = ?
    GROUP BY e.engagement_id, e.client_name, e.total_available_hours, e.status, e.notes
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $engagement_id);
$stmt->execute();
$result = $stmt->get_result();
$engagement = $result->fetch_assoc();

if ($engagement) {
    header('Content-Type: application/json');
    echo json_encode($engagement);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Engagement not found']);
}

$stmt->close();
$conn->close();
?>
