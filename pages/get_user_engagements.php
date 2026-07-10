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
    SELECT e.engagement_id, e.client_name, e.status, en.week_start, SUM(en.assigned_hours) AS week_hours
    FROM entries en
    JOIN engagements e ON en.engagement_id = e.engagement_id
    WHERE en.user_id = ?
    GROUP BY e.engagement_id, e.client_name, e.status, en.week_start
    ORDER BY e.client_name ASC, en.week_start ASC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$engagements = [];
while ($row = $result->fetch_assoc()) {
    $engId = (int)$row['engagement_id'];
    if (!isset($engagements[$engId])) {
        $engagements[$engId] = [
            'engagement_id' => $engId,
            'client_name' => $row['client_name'],
            'status' => $row['status'],
            'weeks' => [],
            'total_hours' => 0
        ];
    }
    $weekHours = (float)$row['week_hours'];
    $engagements[$engId]['weeks'][] = [
        'week_start' => $row['week_start'],
        'hours' => $weekHours
    ];
    $engagements[$engId]['total_hours'] += $weekHours;
}
$engagements = array_values($engagements);
$stmt->close();

echo json_encode(['engagements' => $engagements]);
