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
$weekEndDate   = date('Y-m-d', strtotime('+6 days', $selectedMonday));

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
        e.assigned_hours,
        e.is_timeoff,
        eng.client_name,
        eng.status
    FROM entries e
    JOIN engagements eng ON e.engagement_id = eng.engagement_id
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

$netHours = max(0, $totalHours - $timeOffTotal);
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
.card { min-width: 120px; }
.week-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 0.75rem;
    width: 140px;
}
.week-card.current {
    background-color: #d3f9d8;
}
.timeoff-card {
    border: 1px dashed #ff9800;
    background: #fff8f0;
}
</style>
</head>
<body class="d-flex">
<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">My Schedule</h3>
      <p class="text-muted mb-0">Your personal schedule and time allocation</p>
    </div>
    <div class="header-buttons">
      <a href="#" onclick="location.reload();" class="badge text-black p-2 text-decoration-none fw-medium me-1" style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
        <i class="bi bi-arrow-clockwise me-3"></i>Refresh
      </a>
    </div>
  </div>

  <!-- 8-Week Overview -->
  <div class="week_overview_header mb-3">
    <h6>8-Week Overview</h6>
  </div>
  <div class="d-flex flex-wrap gap-3 mb-5">
    <?php foreach ($mondays as $monday): 
        $weekKey = date('Y-m-d', $monday);
        $assigned = $totalAssignedHours[$weekKey] ?? 0;
        $timeOff = $timeOffHours[$weekKey] ?? 0;
        $net = max(0, $assigned - $timeOff);
        $isCurrent = ($monday == $currentMonday);
    ?>
        <div class="week-card text-center <?php echo $isCurrent ? 'current' : ''; ?>">
            <div class="fw-bold">Week of <?php echo date('n/j', $monday); ?></div>
            <div><?php echo $net; ?> hrs</div>
            <?php if ($timeOff > 0): ?>
                <small class="text-muted"><i class="bi bi-calendar-x"></i> -<?php echo $timeOff; ?> hrs</small>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
  </div>

  <!-- Selected Week View -->
  <div class="d-flex justify-content-between align-items-center mb-3 p-4" style="background-color: rgb(249,249,250); border-radius: 15px;">
    <a href="?week_start=<?php echo $prevWeekMonday; ?>" class="btn btn-outline-secondary btn-sm week-btn">
      <i class="bi bi-chevron-left"></i> Previous Week
    </a>
    <div class="text-center">
      <h5 class="mb-0">Week of <?php echo date('n/j', $selectedMonday); ?></h5>
      <small><?php echo date('M j', $selectedMonday) . " - " . date('M j', strtotime($weekEndDate)); ?></small>
    </div>
    <a href="?week_start=<?php echo $nextWeekMonday; ?>" class="btn btn-outline-secondary btn-sm week-btn">
      Next Week <i class="bi bi-chevron-right"></i>
    </a>
  </div>

  <div class="list-group mb-3">
    <?php foreach ($engagements as $eng): ?>
      <div class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-bold"><?php echo htmlspecialchars($eng['client_name']); ?></div>
        </div>
        <div class="text-end">
          <div class="fw-bold"><?php echo $eng['assigned_hours']; ?>h</div>
          <small class="badge bg-light text-dark"><?php echo htmlspecialchars($eng['status']); ?></small>
        </div>
      </div>
    <?php endforeach; ?>

    <?php foreach ($timeOffs as $off): ?>
      <div class="list-group-item d-flex justify-content-between align-items-center timeoff-card">
        <div>
          <div class="fw-bold"><?php echo htmlspecialchars($off['client_name']); ?></div>
          <small class="text-muted">Approved time off</small>
        </div>
        <div class="text-end text-danger fw-bold">
          -<?php echo $off['assigned_hours']; ?>h
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Week Summary -->
  <div class="list-group-item d-flex justify-content-between align-items-center bg-light">
    <div>
      <strong>Week of <?php echo date('n/j', $selectedMonday); ?> Summary</strong><br>
      <small><?php echo count($engagements); ?> active engagement(s) &bull; <?php echo $timeOffTotal; ?>h time off</small>
    </div>
    <div class="fw-bold"><?php echo $netHours; ?>h Net Hours</div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
