<?php
require_once __DIR__ . '/../includes/session_init.php';
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/avatar_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';

if ($isAdmin) {
    header("Location: master-schedule.php" . (isset($_GET['welcome']) ? '?welcome=1' : ''));
    exit();
}

$userRole = strtolower($_SESSION['user_role'] ?? '');
$restrictEngagementFinancials = in_array($userRole, ['staff', 'senior'], true);

$userId = $_SESSION['user_id'];

// ------------------------------------------------------
// WEEK NAVIGATION LOGIC
$today         = strtotime('today');
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
$mondays     = [];
for ($i = 0; $i < 8; $i++) {
    $mondays[] = strtotime("+{$i} weeks", $startMonday);
}

$startDateRange = date('Y-m-d', $startMonday);
$endDateRange   = date('Y-m-d', strtotime('+7 weeks', $startMonday));

// ------------------------------------------------------
// Get engagements hours
$sqlEntries = "
    SELECT week_start, assigned_hours
    FROM entries
    WHERE user_id = ?
      AND week_start BETWEEN ? AND ?
";
$stmt = $conn->prepare($sqlEntries);
if (!$stmt) {
    die("SQL error in entries query: " . $conn->error);
}
$stmt->bind_param('iss', $userId, $startDateRange, $endDateRange);
$stmt->execute();
$result = $stmt->get_result();

$totalAssignedHours = [];
while ($row = $result->fetch_assoc()) {
    $week = $row['week_start'];
    if (!isset($totalAssignedHours[$week])) $totalAssignedHours[$week] = 0;
    $totalAssignedHours[$week] += floatval($row['assigned_hours']);
}
$stmt->close();

// ------------------------------------------------------
// Get time off (approved individual time off only - matches how Master Schedule
// filters, so a still-pending request doesn't visually read as scheduled time off)
$sqlTimeOff = "
    SELECT week_start, assigned_hours
    FROM time_off
    WHERE user_id = ? AND is_global_timeoff = 0 AND status = 'approved'
      AND week_start BETWEEN ? AND ?
";
$stmt = $conn->prepare($sqlTimeOff);
if (!$stmt) {
    die("SQL error in time_off query: " . $conn->error);
}
$stmt->bind_param('iss', $userId, $startDateRange, $endDateRange);
$stmt->execute();
$resTO = $stmt->get_result();

$timeOffHours = [];
while ($row = $resTO->fetch_assoc()) {
    $week = $row['week_start'];
    if (!isset($timeOffHours[$week])) $timeOffHours[$week] = 0;
    $timeOffHours[$week] += floatval($row['assigned_hours']);
}
$stmt->close();

// ------------------------------------------------------
// Selected week details
$sqlWeekDetails = "
    SELECT e.entry_id, e.assigned_hours, e.engagement_id, eng.client_name, eng.status
    FROM entries e
    LEFT JOIN engagements eng ON e.engagement_id = eng.engagement_id
    WHERE e.user_id = ?
      AND e.week_start = ?
";
$stmt = $conn->prepare($sqlWeekDetails);
if (!$stmt) {
    die("SQL error in week details query: " . $conn->error);
}
$stmt->bind_param('is', $userId, $weekStartDate);
$stmt->execute();
$weekResult = $stmt->get_result();

$engagements = [];
$totalHours  = 0;
while ($row = $weekResult->fetch_assoc()) {
    $engagements[] = $row;
    $totalHours   += floatval($row['assigned_hours']);
}
$stmt->close();

// ------------------------------------------------------
// Time off for selected week - grouped by request_group so a multi-day
// request shows as one row instead of one card per day. Includes
// pending/changes_requested too (not just approved) so a submitted
// request shows up here right away instead of only after review.
$sqlWeekTO = "
    SELECT timeoff_id, request_group, category, holiday_date, assigned_hours, status
    FROM time_off
    WHERE user_id = ? AND is_global_timeoff = 0
      AND status IN ('approved', 'pending', 'changes_requested')
      AND week_start = ?
";
$stmt = $conn->prepare($sqlWeekTO);
if (!$stmt) {
    die("SQL error in week TO query: " . $conn->error);
}
$stmt->bind_param('is', $userId, $weekStartDate);
$stmt->execute();
$weekTORes = $stmt->get_result();

$timeOffGroups = [];
$timeOffTotal  = 0;
while ($row = $weekTORes->fetch_assoc()) {
    $groupKey = $row['request_group'] ?: ('single-' . $row['timeoff_id']);
    if (!isset($timeOffGroups[$groupKey])) {
        $timeOffGroups[$groupKey] = [
            'category' => $row['category'] ?: 'vacation',
            'status' => $row['status'],
            'days' => [],
            'total_hours' => 0,
        ];
    }
    $timeOffGroups[$groupKey]['days'][] = $row['holiday_date'];
    $timeOffGroups[$groupKey]['total_hours'] += floatval($row['assigned_hours']);
    $timeOffTotal += floatval($row['assigned_hours']);
}
$stmt->close();
$timeOffs = array_values($timeOffGroups);

$netHours = $totalHours;

// ------------------------------------------------------
// Fetch team members
function getTeamMembers($conn, $engagement_id, $weekStart, $currentUserId) {
    $sql = "
        SELECT u.full_name, e.assigned_hours
        FROM entries e
        JOIN users u ON e.user_id = u.user_id
        WHERE e.engagement_id = ?
          AND e.week_start = ?
          AND e.user_id != ?
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL error in team members query: " . $conn->error);
    }
    $stmt->bind_param('isi', $engagement_id, $weekStart, $currentUserId);
    $stmt->execute();
    $res = $stmt->get_result();
    $members = [];
    while ($row = $res->fetch_assoc()) {
        $members[] = $row['full_name'] . ' (' . $row['assigned_hours'] . ')';
    }
    $stmt->close();
    return $members;
}

$greetingHour = (int) date('G');
if ($greetingHour < 12) {
    $greeting = 'Good morning';
} elseif ($greetingHour < 18) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}
$firstName = trim(explode(' ', $_SESSION['full_name'] ?? '')[0] ?? 'there');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>My Schedule</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">
  <?php include_once '../templates/sidebar.php'; ?>

  <div class="flex-grow-1 p-4" style="margin-left: 250px;">

    <!-- Page header -->
    <div class="ms-header">
      <div class="ms-who">
        <div class="ms-avatar"><?php echo htmlspecialchars(avatar_initials($_SESSION['full_name'] ?? '')); ?></div>
        <div>
          <h3><?php echo htmlspecialchars($greeting); ?>, <?php echo htmlspecialchars($firstName); ?></h3>
          <p class="ms-role-line">
            Your personal schedule and time allocation
            <span class="ms-role-chip"><?php echo htmlspecialchars(role_label($_SESSION['user_role'] ?? '')); ?></span>
          </p>
        </div>
      </div>
      <a href="#" onclick="location.reload(); return false;" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-clockwise me-2"></i> Refresh
      </a>
    </div>

    <!-- 8-Week Overview + navigator, merged -->
    <div class="ms-week-card">
      <div class="ms-week-head">
        <h6>8-Week Overview</h6>
        <div class="ms-week-legend">
          <span class="ms-legend-item"><span class="ms-legend-dot" style="background:var(--primary-color);"></span>Work</span>
          <span class="ms-legend-item"><span class="ms-legend-dot" style="background:var(--secondary-color);"></span>Time off</span>
          <?php if ($selectedMonday != $currentMonday): ?>
          <a href="?week_start=<?php echo date('Y-m-d', $currentMonday); ?>" class="ms-today-btn">
            <i class="bi bi-calendar-event"></i> Jump to current week
          </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="ms-week-strip">
        <a href="?week_start=<?php echo $prevWeekMonday; ?>" class="ms-week-nav-arrow" aria-label="Previous">
          <i class="bi bi-chevron-left"></i>
        </a>
        <div class="ms-week-tiles">
          <?php foreach ($mondays as $monday):
              $weekKey = date('Y-m-d', $monday);
              $assigned = $totalAssignedHours[$weekKey] ?? 0;
              $timeOff = $timeOffHours[$weekKey] ?? 0;
              $total = $assigned + $timeOff;
              $isSelected = ($monday == $selectedMonday);
              $isToday = ($monday == $currentMonday);
              $workPct = $total > 0 ? ($assigned / $total) * 100 : 0;
              $offPct = $total > 0 ? ($timeOff / $total) * 100 : 0;
          ?>
            <a href="?week_start=<?php echo $weekKey; ?>"
               class="ms-week-tile <?php echo $isSelected ? 'selected' : ''; ?> <?php echo $isToday ? 'today-marker' : ''; ?> <?php echo $total > 0 ? 'has-hours' : ''; ?>">
              <div class="ms-wk-label">Week of <?php echo date('n/j', $monday); ?></div>
              <div class="ms-wk-hours"><?php echo $total; ?>h</div>
              <div class="ms-wk-bar-track">
                <div class="ms-wk-bar-work" style="width:<?php echo $workPct; ?>%"></div>
                <div class="ms-wk-bar-off" style="width:<?php echo $offPct; ?>%"></div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
        <a href="?week_start=<?php echo $nextWeekMonday; ?>" class="ms-week-nav-arrow" aria-label="Next">
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
    </div>

    <!-- Selected Week -->
    <div class="ms-detail-head">
      <div class="ms-title-group">
        <h5>Week of <?php echo date('n/j', $selectedMonday); ?></h5>
        <span class="ms-date-range"><?php echo date('M j', $selectedMonday) . " - " . date('M j', strtotime($weekEndDate)); ?></span>
        <?php if ($selectedMonday == $currentMonday): ?>
          <span class="ms-current-pill"><i class="bi bi-circle-fill" style="font-size:6px;"></i> Current Week</span>
        <?php endif; ?>
      </div>
      <div class="ms-summary-inline">
        <?php echo count($engagements); ?> engagement<?php echo count($engagements) === 1 ? '' : 's'; ?>
        <?php if (!empty($timeOffs)): ?>
          · <?php echo count($timeOffs); ?> time off request<?php echo count($timeOffs) === 1 ? '' : 's'; ?>
        <?php endif; ?>
        · <b><?php echo $netHours + $timeOffTotal; ?> hrs</b> total
      </div>
    </div>

    <?php if (empty($engagements) && empty($timeOffs)): ?>
      <div class="ms-empty-week">
        <i class="bi bi-calendar2-check"></i>
        <div class="t">Nothing scheduled this week</div>
        <div>You have a clear schedule.</div>
      </div>
    <?php else: ?>
      <div class="ms-entry-list">
        <?php foreach ($engagements as $eng):
          $teamMembers = getTeamMembers($conn, $eng['engagement_id'], $weekStartDate, $userId);
          $clientName = $eng['client_name'] ?? 'Unknown';
          $status = strtolower($eng['status'] ?? 'confirmed');
          $statusClass = in_array($status, ['confirmed', 'pending', 'not_confirmed'], true) ? str_replace('_', '-', $status) : 'confirmed';
          $statusLabel = $status === 'not_confirmed' ? 'Not Confirmed' : ucfirst($status);
        ?>
          <div class="ms-entry-row view-engagement-btn" role="button" tabindex="0"
               data-engagement-id="<?php echo $eng['engagement_id']; ?>"
               data-avatar-color="<?php echo avatar_color($clientName); ?>"
               data-initials="<?php echo htmlspecialchars(avatar_initials($clientName)); ?>"
               data-restrict-financials="<?php echo $restrictEngagementFinancials ? '1' : '0'; ?>">
            <div class="ms-entry-avatar" style="background-color:<?php echo avatar_color($clientName); ?>;"><?php echo htmlspecialchars(avatar_initials($clientName)); ?></div>
            <div class="ms-entry-main">
              <div class="ms-entry-name"><?php echo htmlspecialchars($clientName); ?></div>
              <div class="ms-entry-team">Team: <b><?php echo !empty($teamMembers) ? htmlspecialchars(implode(', ', $teamMembers)) : 'Just you'; ?></b></div>
            </div>
            <span class="eng-status-pill <?php echo $statusClass; ?>"><span class="dot"></span><?php echo htmlspecialchars($statusLabel); ?></span>
            <div class="ms-entry-hours"><?php echo $eng['assigned_hours']; ?>h</div>
          </div>
        <?php endforeach; ?>

        <?php
          $offStatusMap = [
            'approved' => ['confirmed', 'Approved'],
            'pending' => ['pending', 'Pending'],
            'changes_requested' => ['not-confirmed', 'Changes Requested'],
          ];
        ?>
        <?php foreach ($timeOffs as $off):
          sort($off['days']);
          $dayCount = count($off['days']);
          $dayLabel = $dayCount === 1
            ? date('D M j', strtotime($off['days'][0]))
            : date('D M j', strtotime($off['days'][0])) . ' – ' . date('D M j', strtotime(end($off['days'])));
          [$offStatusClass, $offStatusLabel] = $offStatusMap[$off['status']] ?? ['pending', 'Pending'];
        ?>
          <div class="ms-entry-row timeoff">
            <div class="ms-entry-avatar"><i class="bi bi-airplane-fill"></i></div>
            <div class="ms-entry-main">
              <div class="ms-entry-name">Time Off <span class="text-muted" style="font-weight:500;">· <?php echo $dayCount; ?> day<?php echo $dayCount === 1 ? '' : 's'; ?></span></div>
              <div class="ms-entry-team"><?php echo htmlspecialchars(ucfirst($off['category'])); ?> · <?php echo htmlspecialchars($dayLabel); ?></div>
            </div>
            <span class="eng-status-pill <?php echo $offStatusClass; ?>"><span class="dot"></span><?php echo $offStatusLabel; ?></span>
            <div class="ms-entry-hours"><?php echo $off['total_hours']; ?>h</div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>

  <?php include_once '../includes/modals/viewProfileModal.php'; ?>
  <?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>
  <?php include_once '../includes/modals/view_engagement_modal.php'; ?>

  <script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
  <script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
  <script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
  <script src="../assets/js/view_engagement_modal.js?v=<?php echo time(); ?>"></script>
</body>
</html>
