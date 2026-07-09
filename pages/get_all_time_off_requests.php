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
    SELECT t.timeoff_id, t.request_group, t.user_id, u.full_name, u.role,
           t.category, t.holiday_date, t.week_start, t.assigned_hours, t.timeoff_note,
           t.status, t.reviewer_comment, t.reviewed_by, t.reviewed_at, t.created,
           r.full_name AS reviewed_by_name
    FROM time_off t
    JOIN users u ON t.user_id = u.user_id
    LEFT JOIN users r ON t.reviewed_by = r.user_id
    WHERE t.is_global_timeoff = 0
    ORDER BY COALESCE(t.holiday_date, t.week_start) ASC
");
$stmt->execute();
$result = $stmt->get_result();

$groups = [];
while ($row = $result->fetch_assoc()) {
    $groupKey = $row['request_group'] ?: ('single-' . $row['timeoff_id']);
    if (!isset($groups[$groupKey])) {
        $groups[$groupKey] = [
            'request_group' => $groupKey,
            'user_id' => (int)$row['user_id'],
            'full_name' => $row['full_name'],
            'role' => $row['role'],
            'category' => $row['category'] ?: 'vacation',
            'reason' => $row['timeoff_note'] ?? '',
            'status' => $row['status'],
            'reviewer_comment' => $row['reviewer_comment'],
            'reviewed_by_name' => $row['reviewed_by_name'],
            'reviewed_at' => $row['reviewed_at'],
            'created' => $row['created'],
            'days' => [],
            'total_hours' => 0
        ];
    }
    $groups[$groupKey]['days'][] = [
        'timeoff_id' => (int)$row['timeoff_id'],
        'date' => $row['holiday_date'] ?? $row['week_start'],
        'hours' => (float)$row['assigned_hours']
    ];
    $groups[$groupKey]['total_hours'] += (float)$row['assigned_hours'];
}
$stmt->close();

$requests = array_values($groups);
usort($requests, function ($a, $b) {
    $aPending = $a['status'] === 'pending' ? 0 : 1;
    $bPending = $b['status'] === 'pending' ? 0 : 1;
    if ($aPending !== $bPending) return $aPending <=> $bPending;
    return strcmp($b['created'] ?? '', $a['created'] ?? '');
});

echo json_encode(['requests' => $requests]);
