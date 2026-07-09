<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
header('Content-Type: application/json');

$userRole = strtolower($_SESSION['user_role'] ?? '');
if (!isset($_SESSION['user_id']) || ($userRole !== 'admin' && $userRole !== 'manager')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$stmt = $conn->prepare("
    SELECT t.timeoff_id, t.user_id, u.full_name, u.role, t.holiday_date, t.week_start,
           t.assigned_hours, t.timeoff_note, t.status, t.reviewed_by, t.reviewed_at, t.created,
           r.full_name AS reviewed_by_name
    FROM time_off t
    JOIN users u ON t.user_id = u.user_id
    LEFT JOIN users r ON t.reviewed_by = r.user_id
    WHERE t.is_global_timeoff = 0
    ORDER BY (t.status = 'pending') DESC, COALESCE(t.holiday_date, t.week_start) DESC
");
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = [
        'timeoff_id' => (int)$row['timeoff_id'],
        'user_id' => (int)$row['user_id'],
        'full_name' => $row['full_name'],
        'role' => $row['role'],
        'date' => $row['holiday_date'] ?? $row['week_start'],
        'hours' => (float)$row['assigned_hours'],
        'reason' => $row['timeoff_note'] ?? '',
        'status' => $row['status'],
        'reviewed_by_name' => $row['reviewed_by_name'],
        'reviewed_at' => $row['reviewed_at'],
        'created' => $row['created']
    ];
}
$stmt->close();

echo json_encode(['requests' => $requests]);
