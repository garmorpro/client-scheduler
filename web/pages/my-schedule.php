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
        // ONLY count regular assignments
        $totalAssignedHours[$week] += floatval($row['assigned_hours']);
    }
}
$stmt->close();

// ------------------------------------------------------
// SELECTED WEEK DETAILS (INCLUDE TIME OFF)
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
        // ONLY sum regular assignments
        $totalHours += floatval($row['assigned_hours']);
    }
}
$stmt->close();

$netHours = $totalHours; // now time off is NOT subtracted

// ------------------------------------------------------
// FETCH TEAM MEMBERS FOR EACH ENGAGEMENT (EXCLUDE CURRENT USER)
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
<link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
<style>
.card { min-width: 120px; margin-bottom: 15px; }
.timeoff-card { 
    border: 2px dashed rgb(209,226, 159);
    background: rgb(246, 249, 236);
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
        $net = $assigned; // now only regular assignments, ignore time off
        $isCurrent = ($monday == $currentMonday);
    ?>
        <div class="week-card text-center <?php echo $isCurrent ? 'current' : ''; ?>">
            <div class="fw-semibold" style="font-size: 14px;">Week of <?php echo date('n/j', $monday); ?></div>
            <div class="fw-bold fs-4"><?php echo $net; ?> hrs</div>
            <?php if ($timeOff > 0): ?>
                <small class="text-danger fw-semibold" style="font-size: 10px;"><?php echo $timeOff; ?>hrs time off</small>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
  </div>

  <!-- Selected Week View -->
  <div class="d-flex justify-content-between shadow-sm align-items-center mb-3 p-4" style="background-color: rgb(249,249,250); border-radius: 15px;">
    <a href="?week_start=<?php echo $prevWeekMonday; ?>" class="btn btn-outline-secondary btn-sm week-btn">
      <i class="bi bi-chevron-left me-2"></i>Previous Week
    </a>
    <div class="text-center">
      <div class="text-status text-confirmed">Current</div>
      <div class="mb-0 fw-semibold fs-5">Week of <?php echo date('n/j', $selectedMonday); ?></div>
      <small class="text-muted"><?php echo date('M j', $selectedMonday) . " - " . date('M j', strtotime($weekEndDate)); ?></small>
    </div>
    <a href="?week_start=<?php echo $nextWeekMonday; ?>" class="btn btn-outline-secondary btn-sm week-btn">
      Next Week<i class="bi bi-chevron-right ms-2"></i>
    </a>
  </div>

  <!-- Detailed Week Entries as Cards -->
  <div class="d-flex flex-column mb-3">
    <?php foreach ($engagements as $eng): 
      $teamMembers = getTeamMembers($conn, $eng['engagement_id'], $weekStartDate, $userId);
    ?>
    <div class="card p-3 shadow-sm mb-3">
      <div class="d-flex justify-content-between align-items-start mb-2">
      <!-- Left side: Client name and team members -->
        <div>
          <div class="fw-semibold fs-5"><?php echo htmlspecialchars($eng['client_name']); ?></div>
            <small class="text-muted">
              <strong>Team member(s):</strong>
                <?php echo !empty($teamMembers) ? implode(', ', $teamMembers) : 'no other team members assigned'; ?>
            </small>
          </div>
        
          <!-- Right side: Hours and status -->
          <div class="text-end">
            <div class="fw-semibold fs-5"><?php echo $eng['assigned_hours']; ?> hrs</div>
            <?php
              $status = strtolower($eng['status'] ?? 'confirmed');
              switch ($status) {
                case 'confirmed': $status_class = 'text-confirmed'; $status_format = 'Confirmed'; break;
                case 'pending': $status_class = 'text-pending'; $status_format = 'Pending'; break;
                case 'not_confirmed': $status_class = 'text-not-confirmed'; $status_format = 'Not Confirmed'; break;
                default: $status_class = 'text-danger'; $status_format = 'Error'; break;
              }
            ?>
          <small class="text-status <?php echo $status_class; ?>"><?php echo $status_format; ?></small>
        </div>
      </div>
    </div>
    <?php endforeach; ?>



    <?php foreach ($timeOffs as $off): ?>
      <div class="card p-3 shadow-sm timeoff-card">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-semibold fs-5"><?php echo htmlspecialchars($off['client_name']); ?></div>
            <small class="text-muted">Approved time off</small>
          </div>
          <div class="text-end text-danger fw-semibold fs-5">
            <?php echo $off['assigned_hours']; ?>hrs
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Week Summary -->
<div class="list-group-item d-flex justify-content-between align-items-center bg-light p-4" style="background-color: rgb(249,249,250); border-radius: 15px;">
    <!-- Left side: summary info -->
    <div>
        <div class="fw-semibold fs-5">Week of <?php echo date('n/j', $selectedMonday); ?> Summary</div>
        <small class="text-muted"><?php echo count($engagements); ?> active engagement(s) <i class="bi bi-dot"></i> <?php echo $timeOffTotal; ?>hrs time off</small>
    </div>

    <!-- Right side: Net hours -->
    <div class="text-end">
        <div class="fw-semibold fs-5"><?php echo $netHours; ?>hrs</div>
        <div class="text-muted fw-normal" style="font-size: 14px;">Net Hours</div>
    </div>
</div>


</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
