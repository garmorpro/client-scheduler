<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'view_master_schedule')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$weekStart = $_GET['week_start'] ?? '';
if (!$weekStart || !DateTime::createFromFormat('Y-m-d', $weekStart)) {
    echo json_encode(['success' => false, 'error' => 'Invalid week']);
    exit();
}

// Same 40/50 threshold logic as master-schedule.php itself.
$STANDARD_THRESHOLD = 40;
$BUSY_SEASON_THRESHOLD = 50;

$busySeasonStart = null;
$busySeasonEnd = null;
$bsResult = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_master_key = 'busy_season'");
if ($bsResult) {
    while ($row = $bsResult->fetch_assoc()) {
        if ($row['setting_key'] === 'start_date') $busySeasonStart = $row['setting_value'];
        if ($row['setting_key'] === 'end_date') $busySeasonEnd = $row['setting_value'];
    }
}
$isBusySeason = $busySeasonStart && $busySeasonEnd && $weekStart >= $busySeasonStart && $weekStart <= $busySeasonEnd;
$threshold = $isBusySeason ? $BUSY_SEASON_THRESHOLD : $STANDARD_THRESHOLD;

// Firm-wide time off (a company holiday landing in this week) eats into
// everyone's capacity equally.
$globalStmt = $conn->prepare("SELECT COALESCE(SUM(assigned_hours), 0) AS hrs FROM time_off WHERE is_global_timeoff = 1 AND week_start = ?");
$globalStmt->bind_param('s', $weekStart);
$globalStmt->execute();
$globalHours = (float) ($globalStmt->get_result()->fetch_assoc()['hrs'] ?? 0);
$globalStmt->close();

// Same roster Master Schedule itself staffs (active, non-admin/non-CRM
// roles), each with their own assigned-hours and approved-time-off totals
// for this specific week, via correlated subqueries rather than a JOIN so a
// person with several entries/time-off rows can't fan out and double count.
$stmt = $conn->prepare("
    SELECT u.user_id, u.full_name, u.role,
        COALESCE((SELECT SUM(a.assigned_hours) FROM entries a WHERE a.user_id = u.user_id AND a.week_start = ?), 0) AS assigned_hours,
        COALESCE((SELECT SUM(t.assigned_hours) FROM time_off t WHERE t.user_id = u.user_id AND t.week_start = ? AND t.is_global_timeoff = 0 AND t.status = 'approved'), 0) AS personal_timeoff_hours
    FROM users u
    WHERE u.status = 'active' AND u.role IN ('intern', 'staff', 'senior', 'manager')
    ORDER BY u.full_name ASC
");
$stmt->bind_param('ss', $weekStart, $weekStart);
$stmt->execute();
$result = $stmt->get_result();

$available = [];
while ($row = $result->fetch_assoc()) {
    $used = (float) $row['assigned_hours'] + (float) $row['personal_timeoff_hours'] + $globalHours;
    $availableHours = round($threshold - $used, 1);
    if ($availableHours <= 0) continue; // fully booked or over - not "available"

    $available[] = [
        'user_id' => (int) $row['user_id'],
        'full_name' => $row['full_name'],
        'role' => $row['role'],
        'used_hours' => round($used, 1),
        'available_hours' => $availableHours,
    ];
}
$stmt->close();

usort($available, function ($a, $b) {
    return $b['available_hours'] <=> $a['available_hours'];
});

echo json_encode([
    'success' => true,
    'week_start' => $weekStart,
    'threshold' => $threshold,
    'is_busy_season' => $isBusySeason,
    'global_hours' => $globalHours,
    'employees' => $available,
]);
