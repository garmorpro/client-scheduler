<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$week_start = $_GET['week_start'] ?? '';

if (!$user_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $week_start)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// Example query joining assignments and engagements for client names
$sql = "SELECT a.assignment_id, a.assigned_hours, a.is_timeoff, e.client_name
        FROM assignments a
        LEFT JOIN engagements e ON a.engagement_id = e.engagement_id
        WHERE a.user_id = ? AND a.week_start = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $user_id, $week_start);
$stmt->execute();
$result = $stmt->get_result();

$assignments = [];
while ($row = $result->fetch_assoc()) {
    $assignments[] = [
        'assignment_id' => $row['assignment_id'],
        'assigned_hours' => floatval($row['assigned_hours']),
        'client_name' => $row['is_timeoff'] ? null : $row['client_name'],
        'type' => $row['is_timeoff'] ? 'Time Off' : 'Regular',
    ];
}

echo json_encode($assignments);
