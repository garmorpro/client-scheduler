<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT timeoff_id, holiday_date, week_start, assigned_hours, timeoff_note, status, reviewed_at, created
    FROM time_off
    WHERE user_id = ? AND is_global_timeoff = 0
    ORDER BY COALESCE(holiday_date, week_start) DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = [
        'timeoff_id' => (int)$row['timeoff_id'],
        'date' => $row['holiday_date'] ?? $row['week_start'],
        'hours' => (float)$row['assigned_hours'],
        'reason' => $row['timeoff_note'] ?? '',
        'status' => $row['status'],
        'reviewed_at' => $row['reviewed_at'],
        'created' => $row['created']
    ];
}
$stmt->close();

echo json_encode(['requests' => $requests]);
