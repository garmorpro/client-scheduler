<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';

$today = strtotime('today');
$currentMonday = strtotime('monday this week', $today);
$weekOffset = isset($_GET['week_offset']) ? intval($_GET['week_offset']) : 0;
$startMonday = strtotime("-2 weeks", $currentMonday);
$startMonday = strtotime("+{$weekOffset} weeks", $startMonday);

$mondays = [];
for ($i = 0; $i < 7; $i++) {
    $mondays[] = strtotime("+{$i} weeks", $startMonday);
}

$firstWeek = reset($mondays);
$lastWeek = end($mondays);
$rangeLabel = "Week of " . date('n/j', $firstWeek) . " - Week of " . date('n/j', $lastWeek);

// ----------------------------------------------
// 1. Get engagements assigned to current user
$sqlEngagements = "
    SELECT DISTINCT e.engagement_id, e.client_name
    FROM entries AS en
    JOIN engagements AS e ON en.engagement_id = e.engagement_id
    WHERE en.user_id = ?
    ORDER BY e.client_name
    LIMIT 25
";

$stmt = $conn->prepare($sqlEngagements);
if (!$stmt) {
    die("Prepare failed (engagements): (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$engagements = [];
while ($row = $result->fetch_assoc()) {
    $engagements[$row['engagement_id']] = $row['client_name'];
}
$stmt->close();

// ----------------------------------------------
// 2. Fetch all entries for current user for these weeks and engagements
$startDate = date('Y-m-d', $startMonday);
$endDate = date('Y-m-d', strtotime('+6 weeks', $startMonday));

$sqlEntries = "
    SELECT 
        entry_id,
        engagement_id,
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

$entries = [];  // [engagement_id][week_start][] = entry data
$timeOff = [];  // [week_start] = total time off hours

while ($row = $result->fetch_assoc()) {
    $week = $row['week_start'];
    if (!empty($row['is_timeoff']) && $row['is_timeoff'] == 1) {
        if (!isset($timeOff[$week])) $timeOff[$week] = 0;
        $timeOff[$week] += floatval($row['assigned_hours']);
    } else {
        $engId = $row['engagement_id'];
        if (!isset($entries[$engId])) $entries[$engId] = [];
        if (!isset($entries[$engId][$week])) $entries[$engId][$week] = [];
        $entries[$engId][$week][] = $row;
    }
}
$stmt->close();

// ----------------------------------------------
// 3. Calculate total assigned hours (sum assigned hours for all engagements per week)
$totalAssignedHours = [];
foreach ($mondays as $monday) {
    $weekKey = date('Y-m-d', $monday);
    $totalAssignedHours[$weekKey] = 0;
    foreach ($engagements as $engId => $clientName) {
        if (isset($entries[$engId][$weekKey])) {
            foreach ($entries[$engId][$weekKey] as $entry) {
                $totalAssignedHours[$weekKey] += floatval($entry['assigned_hours']);
            }
        }
    }
}

// Subtract time off hours
foreach ($totalAssignedHours as $week => $hours) {
    $timeOffHours = $timeOff[$week] ?? 0;
    $totalAssignedHours[$week] = max(0, $hours - $timeOffHours);
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
.highlight-today {
    background-color: #d3f9d8;
}
</style>
</head>
<body class="d-flex">
<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4">
  <h2>My Schedule</h2>
  <p><?php echo htmlspecialchars($rangeLabel); ?></p>

  <div class="table-responsive">
    <table class="table table-bordered align-middle text-center">
      <thead class="table-light">
        <tr>
          <th class="text-start align-middle">Engagement</th>
          <?php foreach ($mondays as $idx => $monday): 
            $weekStart = $monday;
            $isCurrent = ($idx === array_search($currentMonday, $mondays));
          ?>
          <th class="<?php echo $isCurrent ? 'highlight-today' : ''; ?>">
            <?php echo date('M j', $weekStart); ?><br />
            <small class="text-muted">Week of <?php echo date('n/j', $weekStart); ?></small>
          </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($engagements as $engId => $clientName): ?>
        <tr>
          <td class="text-start"><?php echo htmlspecialchars($clientName); ?></td>
          <?php foreach ($mondays as $monday): 
            $weekKey = date('Y-m-d', $monday);
            $cellEntries = $entries[$engId][$weekKey] ?? [];
            $cellContent = '';

            if (!empty($cellEntries)) {
                foreach ($cellEntries as $entry) {
                    $engagementStatus = strtolower($entry['engagement_status'] ?? 'confirmed');
                    switch ($engagementStatus) {
                        case 'confirmed': $badgeColor = 'success'; break;
                        case 'pending': $badgeColor = 'purple'; break;
                        case 'not_confirmed': $badgeColor = 'primary'; break;
                        default: $badgeColor = 'secondary'; break;
                    }
                    $assignedHours = htmlspecialchars($entry['assigned_hours']);
                    $cellContent .= "<span class='badge bg-$badgeColor'>{$assignedHours} hrs</span><br>";
                }
            } else {
                $cellContent = "<span class='text-muted'>-</span>";
            }
          ?>
          <td><?php echo $cellContent; ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>

        <!-- Time Off Row -->
        <tr class="table-secondary fw-semibold">
          <td>Time Off</td>
          <?php foreach ($mondays as $monday): 
            $weekKey = date('Y-m-d', $monday);
            $timeOffHours = $timeOff[$weekKey] ?? 0;
          ?>
          <td><?php echo $timeOffHours > 0 ? $timeOffHours . " hrs" : "-"; ?></td>
          <?php endforeach; ?>
        </tr>

        <!-- Total Hours Row -->
        <tr class="table-info fw-semibold">
          <td>Total Hours</td>
          <?php foreach ($mondays as $monday): 
            $weekKey = date('Y-m-d', $monday);
            $totalHours = $totalAssignedHours[$weekKey] ?? 0;
          ?>
          <td><?php echo $totalHours > 0 ? $totalHours . " hrs" : "-"; ?></td>
          <?php endforeach; ?>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
