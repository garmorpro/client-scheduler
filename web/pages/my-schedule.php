<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';

// ------------------------------------------------------
// WEEK NAVIGATION LOGIC
$today = strtotime('today');
$currentMonday = strtotime('monday this week', $today);

if (isset($_GET['week_start'])) {
    $selectedMonday = strtotime($_GET['week_start']);
    if ($selectedMonday === false) {
        $selectedMonday = $currentMonday;
    }
} else {
    $selectedMonday = $currentMonday;
}

$prevWeekMonday = date('Y-m-d', strtotime('-1 week', $selectedMonday));
$nextWeekMonday = date('Y-m-d', strtotime('+1 week', $selectedMonday));

$weekStartDate = date('Y-m-d', $selectedMonday);
$weekEndDate   = date('Y-m-d', strtotime('+4 days', $selectedMonday));

// ------------------------------------------------------
// 8-WEEK OVERVIEW
$startMonday = strtotime('-1 week', $currentMonday);
$mondays = [];
for ($i = 0; $i < 8; $i++) {
    $mondays[] = strtotime("+{$i} weeks", $startMonday);
}

$startDateRange = date('Y-m-d', $startMonday);
$endDateRange = date('Y-m-d', strtotime('+7 weeks', $startMonday));

$sqlEntries = "
    SELECT 
        week_start,
        assigned_hours,
        is_timeoff
    FROM entries
    WHERE user_id = ?
      AND week_start BETWEEN ? AND ?
";
$stmt = $conn->prepare($sqlEntries);
$stmt->bind_param('iss', $userId, $startDateRange, $endDateRange);
$stmt->execute();
$result = $stmt->get_result();

$totalAssignedHours = [];
$timeOffHours = [];
while ($row = $result->fetch_assoc()) {
    $week = $row['week_start'];
    if (!isset($totalAssignedHours[$week])) $totalAssignedHours[$week] = 0;
    if (!isset($timeOffHours[$week])) $timeOffHours[$week] = 0;

    if (!empty($row['is_timeoff']) && $row['is_timeoff'] == 1) {
        $timeOffHours[$week] += floatval($row['assigned_hours']);
    } else {
        $totalAssignedHours[$week] += floatval($row['assigned_hours']);
    }
}
$stmt->close();

// ------------------------------------------------------
// SELECTED WEEK DETAILS
$sqlWeekDetails = "
    SELECT 
        e.entry_id,
        e.assigned_hours,
        e.is_timeoff,
        e.engagement_id,
        COALESCE(eng.client_name, 'Time Off') AS client_name,
        eng.status
    FROM entries e
    LEFT JOIN engagements eng ON e.engagement_id = eng.engagement_id
    WHERE e.user_id = ?
      AND e.week_start = ?
";
$stmt = $conn->prepare($sqlWeekDetails);
$stmt->bind_param('is', $userId, $weekStartDate);
$stmt->execute();
$weekResult = $stmt->get_result();

$engagements = [];
$timeOffs = [];
$totalHours = 0;
$timeOffTotal = 0;

while ($row = $weekResult->fetch_assoc()) {
    if (!empty($row['is_timeoff']) && $row['is_timeoff'] == 1) {
        $timeOffs[] = $row;
        $timeOffTotal += floatval($row['assigned_hours']);
    } else {
        $engagements[] = $row;
        $totalHours += floatval($row['assigned_hours']);
    }
}
$stmt->close();

$netHours = $totalHours;

// ------------------------------------------------------
// FETCH TEAM MEMBERS
function getTeamMembers($conn, $engagement_id, $weekStart, $currentUserId) {
    $sql = "
        SELECT u.first_name, u.last_name, e.assigned_hours
        FROM entries e
        JOIN users u ON e.user_id = u.user_id
        WHERE e.engagement_id = ?
          AND e.week_start = ?
          AND e.user_id != ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isi', $engagement_id, $weekStart, $currentUserId);
    $stmt->execute();
    $res = $stmt->get_result();
    $members = [];
    while ($row = $res->fetch_assoc()) {
        $members[] = $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['assigned_hours'] . ')';
    }
    $stmt->close();
    return $members;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>My Schedule</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background-color: #f5f7fa; font-family: 'Inter', sans-serif; }
.section-title { font-weight: 600; font-size: 1.1rem; color: #495057; margin-bottom: 0.75rem; }
.week-card {
    background: #fff;
    border-radius: 12px;
    padding: 14px;
    min-width: 120px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    transition: all 0.2s ease;
}
.week-card.current {
    border: 2px solid #198754;
    background-color: #ecfdf5;
}
.week-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
}
.current_week {
    background: #198754;
    color: white;
    font-size: 0.75rem;
    padding: 3px 8px;
    border-radius: 12px;
}
.card {
    border: none;
    border-radius: 12px;
}
.timeoff-card {
    background: #fef9f5;
    border: 1px dashed #f59e0b;
}
.text-status { font-size: 0.85rem; font-weight: 500; }
.text-confirmed { color: #198754; }
.text-pending { color: #f59e0b; }
.text-not-confirmed { color: #dc3545; }
</style>
</head>
<body>
<?php include_once '../templates/sidebar.php'; ?>

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Schedule</h2>
            <p class="text-muted mb-0">Track your weekly engagements and time off</p>
        </div>
        <a href="#" onclick="location.reload();" class="btn btn-light border">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </a>
    </div>

    <!-- 8 Week Overview -->
    <div class="section-title">8-Week Overview</div>
    <div class="d-flex flex-wrap gap-3 mb-5">
        <?php foreach ($mondays as $monday): 
            $weekKey = date('Y-m-d', $monday);
            $assigned = $totalAssignedHours[$weekKey] ?? 0;
            $timeOff = $timeOffHours[$weekKey] ?? 0;
            $isCurrent = ($monday == $currentMonday);
        ?>
        <div class="week-card text-center <?php echo $isCurrent ? 'current' : ''; ?>">
            <div class="fw-semibold" style="font-size: 0.85rem;">Week of <?php echo date('n/j', $monday); ?></div>
            <div class="fw-bold fs-4"><?php echo $assigned; ?> hrs</div>
            <?php if ($timeOff > 0): ?>
                <small class="text-danger fw-semibold"><?php echo $timeOff; ?> hrs off</small>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Selected Week -->
    <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-3 shadow-sm mb-4">
        <a href="?week_start=<?php echo $prevWeekMonday; ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-chevron-left me-1"></i>Prev
        </a>
        <div class="text-center" style="min-height: 46px;">
            <div class="current_week mb-1" style="visibility: <?php echo ($selectedMonday == $currentMonday) ? 'visible' : 'hidden'; ?>;">Current</div>
            <div class="fw-semibold fs-5">Week of <?php echo date('n/j', $selectedMonday); ?></div>
            <small class="text-muted"><?php echo date('M j', $selectedMonday) . " - " . date('M j', strtotime($weekEndDate)); ?></small>
        </div>
        <a href="?week_start=<?php echo $nextWeekMonday; ?>" class="btn btn-outline-secondary btn-sm">
            Next<i class="bi bi-chevron-right ms-1"></i>
        </a>
    </div>

    <!-- Week Details -->
    <div class="mb-4">
        <?php if (empty($engagements) && empty($timeOffs)): ?>
            <div class="card p-5 shadow-sm text-center">
                <i class="bi bi-calendar-check fs-1 text-success mb-3"></i>
                <div class="fw-semibold fs-5">No engagements this week</div>
                <small class="text-muted">Enjoy the free time!</small>
            </div>
        <?php else: ?>
            <?php foreach ($engagements as $eng): 
                $teamMembers = getTeamMembers($conn, $eng['engagement_id'], $weekStartDate, $userId);
                $status = strtolower($eng['status'] ?? 'confirmed');
                switch ($status) {
                    case 'confirmed': $status_class = 'text-confirmed'; $status_format = 'Confirmed'; break;
                    case 'pending': $status_class = 'text-pending'; $status_format = 'Pending'; break;
                    case 'not_confirmed': $status_class = 'text-not-confirmed'; $status_format = 'Not Confirmed'; break;
                    default: $status_class = 'text-danger'; $status_format = 'Error'; break;
                }
            ?>
            <div class="card p-3 shadow-sm mb-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="fw-semibold fs-5"><?php echo htmlspecialchars($eng['client_name']); ?></div>
                        <small class="text-muted">Team: <?php echo !empty($teamMembers) ? implode(', ', $teamMembers) : 'Only you assigned'; ?></small>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold fs-5"><?php echo $eng['assigned_hours']; ?> hrs</div>
                        <small class="text-status <?php echo $status_class; ?>"><?php echo $status_format; ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php foreach ($timeOffs as $off): ?>
            <div class="card p-3 shadow-sm timeoff-card mb-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="fw-semibold fs-5"><?php echo htmlspecialchars($off['client_name']); ?></div>
                        <small class="text-muted">Approved Time Off</small>
                    </div>
                    <div class="text-end text-danger fw-semibold fs-5"><?php echo $off['assigned_hours']; ?> hrs</div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Summary -->
    <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-3 shadow-sm">
        <div>
            <div class="fw-semibold fs-5">Summary</div>
            <small class="text-muted"><?php echo count($engagements); ?> engagements · <?php echo $timeOffTotal; ?> hrs off</small>
        </div>
        <div class="text-end">
            <div class="fw-bold fs-5"><?php echo $netHours; ?> hrs</div>
            <div class="text-muted" style="font-size: 0.85rem;">Net Hours</div>
        </div>
    </div>
</div>
</body>
</html>
