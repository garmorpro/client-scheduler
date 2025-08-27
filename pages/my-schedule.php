<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

$isAdmin   = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isManager = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'manager';

// ✅ FIX: should be OR, not AND
if ($isAdmin || $isManager) {
    header("Location: admin-panel.php");
    exit();
}

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
// Get time off
$sqlTimeOff = "
    SELECT week_start, assigned_hours
    FROM time_off
    WHERE user_id = ?
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
    $timeOffHours[$week] += floatval($row['hours']);
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
// Time off for selected week
$sqlWeekTO = "
    SELECT timeoff_id, assigned_hours
    FROM time_off
    WHERE user_id = ?
      AND week_start = ?
";
$stmt = $conn->prepare($sqlWeekTO);
if (!$stmt) {
    die("SQL error in week TO query: " . $conn->error);
}
$stmt->bind_param('is', $userId, $weekStartDate);
$stmt->execute();
$weekTORes = $stmt->get_result();

$timeOffs     = [];
$timeOffTotal = 0;
while ($row = $weekTORes->fetch_assoc()) {
    $timeOffs[] = [
        'id'             => $row['timeoff_id'],
        'assigned_hours' => $row['assigned_hours'],
        'client_name'    => 'Time Off'
    ];
    $timeOffTotal += floatval($row['assigned_hours']);
}
$stmt->close();

$netHours = $totalHours;

// ------------------------------------------------------
// Fetch team members
function getTeamMembers($conn, $engagement_id, $weekStart, $currentUserId) {
    $sql = "
        SELECT u.full_name, e.assigned_hours
        FROM entries e
        JOIN ms_users u ON e.user_id = u.user_id
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
  <style>
    .card { min-width: 200px; margin-bottom: 15px; border-radius: 12px; }
    .timeoff-card { 
      border: 2px dashed #d1e29f;
      background: #f6f9ec;
    }
    .week-card {
      flex: 1 1 calc(12.5% - 10px);
      background: #fff;
      border: 1px solid #eaeaea;
      border-radius: 12px;
      padding: 12px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      min-width: 120px;
    }
    .week-card.current {
      border: 2px solid #0d6efd;
      background: #f0f7ff;
    }
    .text-confirmed { color: #198754; }
    .text-pending { color: #ffc107; }
    .text-not-confirmed { color: #dc3545; }
    .current_week {
      display: inline-block;
      background: #0d6efd;
      color: #fff;
      font-size: 0.75rem;
      padding: 4px 8px;
      border-radius: 6px;
      margin-bottom: 5px;
    }
  </style>
</head>
<body class="d-flex bg-light">
  <?php include_once '../templates/sidebar.php'; ?>

  <div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <!-- Page header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="mb-0 fw-bold">My Schedule</h3>
        <p class="text-muted mb-0">Your personal schedule and time allocation</p>
      </div>
      <div>
        <a href="#" onclick="location.reload();" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-clockwise me-2"></i> Refresh
        </a>
      </div>
    </div>

    <!-- 8-Week Overview -->
    <div class="mb-3">
      <div class="fw-semibold fs-6 mb-2">8-Week Overview</div>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($mondays as $monday): 
            $weekKey = date('Y-m-d', $monday);
            $assigned = $totalAssignedHours[$weekKey] ?? 0;
            $timeOff = $timeOffHours[$weekKey] ?? 0;
            $net = $assigned;
            $isCurrent = ($monday == $currentMonday);
        ?>
          <div class="week-card text-center <?php echo $isCurrent ? 'current' : ''; ?>">
            <div class="fw-semibold small">Week of <?php echo date('n/j', $monday); ?></div>
            <div class="fw-bold fs-4"><?php echo $net; ?> hrs</div>
            <?php if ($timeOff > 0): ?>
              <small class="fw-bold text-success d-block"><?php echo $timeOff; ?> hrs off</small>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Selected Week Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white shadow-sm rounded">
      <a href="?week_start=<?php echo $prevWeekMonday; ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-chevron-left me-1"></i> Previous
      </a>
      <div class="text-center">
        <div class="current_week" style="visibility: <?php echo ($selectedMonday == $currentMonday) ? 'visible' : 'hidden'; ?>;">
          Current Week
        </div>
        <div class="fw-semibold fs-5">Week of <?php echo date('n/j', $selectedMonday); ?></div>
        <small class="text-muted"><?php echo date('M j', $selectedMonday) . " - " . date('M j', strtotime($weekEndDate)); ?></small>
      </div>
      <a href="?week_start=<?php echo $nextWeekMonday; ?>" class="btn btn-outline-secondary btn-sm">
        Next <i class="bi bi-chevron-right ms-1"></i>
      </a>
    </div>

    <!-- Week Entries -->
    <div class="mb-4">
      <?php if (empty($engagements) && empty($timeOffs)): ?>
        <div class="card p-4 text-center shadow-sm">
          <i class="bi bi-calendar2-check fs-1 text-success mb-2"></i>
          <div class="fw-semibold fs-5">No engagements this week</div>
          <small class="text-muted">You have a clear schedule.</small>
        </div>
      <?php else: ?>
        <?php foreach ($engagements as $eng): 
          $teamMembers = getTeamMembers($conn, $eng['engagement_id'], $weekStartDate, $userId);
        ?>
          <div class="card p-3 mb-3 shadow-sm">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold fs-5"><?php echo htmlspecialchars($eng['client_name']); ?></div>
                <small class="text-muted">
                  <strong>Team:</strong> 
                  <?php echo !empty($teamMembers) ? implode(', ', $teamMembers) : 'Just you'; ?>
                </small>
              </div>
              <div class="text-end">
                <div class="fw-semibold fs-5"><?php echo $eng['assigned_hours']; ?> hrs</div>
                <?php
                  $status = strtolower($eng['status'] ?? 'confirmed');
                  switch ($status) {
                    case 'confirmed': $status_class = 'text-confirmed'; $status_label = 'Confirmed'; break;
                    case 'pending': $status_class = 'text-pending'; $status_label = 'Pending'; break;
                    case 'not_confirmed': $status_class = 'text-not-confirmed'; $status_label = 'Not Confirmed'; break;
                    default: $status_class = 'text-danger'; $status_label = 'Error'; break;
                  }
                ?>
                <small class="<?php echo $status_class; ?>"><?php echo $status_label; ?></small>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <?php foreach ($timeOffs as $off): ?>
          <div class="card p-3 mb-3 shadow-sm timeoff-card">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-semibold fs-5"><?php echo htmlspecialchars($off['client_name']); ?></div>
                <small class="text-muted">Approved time off</small>
              </div>
              <div class="fw-semibold fs-5 text-success">
                <?php echo $off['assigned_hours']; ?> hrs
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Week Summary -->
    <div class="d-flex justify-content-between align-items-center p-3 bg-white shadow-sm rounded">
      <div>
        <div class="fw-semibold fs-5">Week of <?php echo date('n/j', $selectedMonday); ?> Summary</div>
        <small class="text-muted">
          <?php echo count($engagements); ?> engagement(s) • <?php echo $timeOffTotal; ?> hrs off
        </small>
      </div>
      <div class="text-end">
        <div class="fw-semibold fs-5"><?php echo $netHours; ?> hrs</div>
        <small class="text-muted">Net Hours</small>
      </div>
    </div>
  </div>

  <script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
