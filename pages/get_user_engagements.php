<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$user_id = (int)$_GET['user_id'];

$stmt = $conn->prepare("
    SELECT e.engagement_id, e.client_name, e.status, SUM(en.assigned_hours) AS total_hours
    FROM entries en
    JOIN engagements e ON en.engagement_id = e.engagement_id
    WHERE en.user_id = ?
    GROUP BY e.engagement_id, e.client_name, e.status
    ORDER BY e.client_name ASC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$engagements = [];
while ($row = $result->fetch_assoc()) {
    $engagements[] = [
        'engagement_id' => (int)$row['engagement_id'],
        'client_name' => $row['client_name'],
        'status' => $row['status'],
        'total_hours' => (float)$row['total_hours']
    ];
}
$stmt->close();

echo json_encode(['engagements' => $engagements]);
