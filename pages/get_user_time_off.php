<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
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

$userId = (int)$_GET['user_id'];
$requesterId = (int)$_SESSION['user_id'];

// Viewing your own time off is always allowed. Otherwise the requester must
// either manage employees generally, or approve time off for this specific
// person (i.e. be their manager) - mirrors the scoping in
// get_all_time_off_requests.php so this endpoint can't be used to read an
// arbitrary employee's time-off history (including private reviewer notes).
if ($userId !== $requesterId) {
    $canManageEmployees = user_has_permission($conn, 'manage_employees');
    $canApproveTimeOff = user_has_permission($conn, 'approve_time_off');

    $isDirectReport = false;
    if ($canApproveTimeOff && !$canManageEmployees) {
        $mgrStmt = $conn->prepare("SELECT manager_id FROM users WHERE user_id = ?");
        $mgrStmt->bind_param('i', $userId);
        $mgrStmt->execute();
        $mgrRow = $mgrStmt->get_result()->fetch_assoc();
        $mgrStmt->close();
        $isDirectReport = $mgrRow && (int)$mgrRow['manager_id'] === $requesterId;
    }

    if (!$canManageEmployees && !$isDirectReport) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

$stmt = $conn->prepare("
    SELECT timeoff_id, request_group, category, holiday_date, week_start, assigned_hours,
           timeoff_note, status, reviewer_comment, reviewed_at, created
    FROM time_off
    WHERE user_id = ? AND is_global_timeoff = 0
    ORDER BY COALESCE(holiday_date, week_start) ASC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$groups = [];
while ($row = $result->fetch_assoc()) {
    $groupKey = $row['request_group'] ?: ('single-' . $row['timeoff_id']);
    if (!isset($groups[$groupKey])) {
        $groups[$groupKey] = [
            'request_group' => $groupKey,
            'category' => $row['category'] ?: 'vacation',
            'reason' => $row['timeoff_note'] ?? '',
            'status' => $row['status'],
            'reviewer_comment' => $row['reviewer_comment'],
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
usort($requests, fn($a, $b) => strcmp($b['created'] ?? '', $a['created'] ?? ''));

echo json_encode(['requests' => $requests]);
