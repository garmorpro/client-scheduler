<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';

// ----------------------------------------------
// 1. Calculate starting Monday (1 week before current week)
$today = strtotime('today');
$currentMonday = strtotime('monday this week', $today);
$startMonday = strtotime('-1 week', $currentMonday);

// Build 8 Mondays
$mondays = [];
for ($i = 0; $i < 8; $i++) {
    $mondays[] = strtotime("+{$i} weeks", $startMonday);
}

// ----------------------------------------------
// 2. Get all entries for current user for the date range
$startDate = date('Y-m-d', $startMonday);
$endDate = date('Y-m-d', strtotime('+7 weeks', $startMonday));

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
if (!$stmt) {
    die("Prepare failed (entries): (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param('iss', $userId, $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$totalAssignedHours = []; // total working hours (excluding time off)
$timeOffHours = [];       // time off only

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
.card {
    min-width: 120px;
}
.highlight-week {
    background-color: #d3f9d8;
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
      <a href="#" 
        onclick="location.reload();" 
        class="badge text-black p-2 text-decoration-none fw-medium me-1" 
        style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
        <i class="bi bi-arrow-clockwise me-3"></i>Refresh
      </a>
    </div>
  </div>

  <div class="d-flex justify-content-between flex-wrap gap-2 ms-3 me-3">
    <?php foreach ($mondays as $monday): 
        $weekKey = date('Y-m-d', $monday);
        $assigned = $totalAssignedHours[$weekKey] ?? 0;
        $timeOff = $timeOffHours[$weekKey] ?? 0;
        $netHours = max(0, $assigned - $timeOff);
    ?>
        <div class="card text-center p-2 <?php echo ($monday == $currentMonday) ? 'highlight-weeks' : ''; ?>" style="width: 150px; height: 100px; border-color: rgb(229,229,229) !important;">
            <div class="fw-bold">
                Week of <?php echo date('n/j', $monday); ?>
            </div>
            <div class="fs-4">
                <?php echo $netHours; ?> hrs
            </div>
            <?php if ($timeOff > 0): ?>
                <div class="text-danger small">
                    (-<?php echo $timeOff; ?> hrs time off)
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
