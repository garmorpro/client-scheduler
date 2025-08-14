<?php
require_once '../includes/db.php';
session_start();

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isManager = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'manager';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$today = strtotime('today');

// Calculate Mondays
$currentMonday = strtotime('monday this week', $today);
$weekOffset = isset($_GET['week_offset']) ? intval($_GET['week_offset']) : 0;
$startMonday = strtotime("-2 weeks", $currentMonday);
$startMonday = strtotime("+{$weekOffset} weeks", $startMonday);

$mondays = [];
$weeksToShow = 24;
for ($i = 0; $i < $weeksToShow; $i++) {
    $mondays[] = strtotime("+{$i} weeks", $startMonday);
}

$firstWeek = reset($mondays);
$lastWeek = end($mondays);
$rangeLabel = "Week of " . date('n/j', $firstWeek) . " - Week of " . date('n/j', $lastWeek);

// Get employees
$employees = [];
$userQuery = "
    SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name, role 
    FROM users 
    WHERE status = 'active' AND role IN ('staff','senior')
    ORDER BY CASE WHEN role='senior' THEN 1 ELSE 2 END, first_name ASC
";
$userResult = $conn->query($userQuery);
while ($row = $userResult->fetch_assoc()) {
    $employees[$row['user_id']] = ['full_name'=>$row['full_name'], 'role'=>$row['role']];
}

// Fetch entries
$startDate = date('Y-m-d', $startMonday);
$endDate = date('Y-m-d', strtotime('+6 weeks', $startMonday));

$query = "
    SELECT a.entry_id, a.user_id, a.engagement_id, e.client_name, a.week_start, a.assigned_hours, e.status AS engagement_status, a.is_timeoff
    FROM entries a
    LEFT JOIN engagements e ON a.engagement_id = e.engagement_id
    WHERE a.week_start BETWEEN ? AND ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$entries = [];
while ($row = $result->fetch_assoc()) {
    $entries[$row['user_id']][$row['week_start']][] = [
        'entry_id' => $row['entry_id'],
        'client_name' => $row['client_name'],
        'assigned_hours' => $row['assigned_hours'],
        'engagement_id' => $row['engagement_id'],
        'engagement_status' => $row['engagement_status'],
        'is_timeoff' => (int)$row['is_timeoff']
    ];
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<title>Master Schedule</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* Only table scrollable wrapper */
.table-wrapper {
    overflow-x: auto;
    width: 100%;
}

.table th:first-child,
.table td:first-child {
    position: sticky;
    left: 0;
    background: #fff;
    z-index: 2;
    box-shadow: 2px 0 5px -2px rgba(0,0,0,0.2);
}

.table thead th:first-child {
    z-index: 3; /* header above body cells */
}

.table th, .table td {
    min-width: 120px;
    white-space: nowrap;
}

.timeoff-cell { background-color: #d9d9d9; position: relative; }
.timeoff-cell:hover { background-color: #e0f7fa; }
.timeoff-corner { position: absolute; top: 2px; right: 6px; font-size: .6rem; color:red; font-weight:600; }
</style>
</head>
<body class="d-flex">
<div class="flex-grow-1 p-4" style="margin-left: 250px;">
<h3>Master Schedule</h3>

<div class="table-wrapper">
<table class="table table-bordered align-middle text-center">
    <thead class="table-light">
        <tr>
            <th class="text-start">Employee</th>
            <?php foreach ($mondays as $idx => $monday): ?>
                <th><?php echo date('M j', $monday); ?><br><small class="text-muted">Week of <?php echo date('n/j', $monday); ?></small></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($employees as $userId => $emp): ?>
        <tr>
            <td class="text-start"><?php echo htmlspecialchars($emp['full_name']); ?><br><small class="text-muted"><?php echo $emp['role']; ?></small></td>
            <?php foreach ($mondays as $monday): 
                $weekKey = date('Y-m-d', $monday);
                $entriesForWeek = $entries[$userId][$weekKey] ?? [];
                $hasTimeOff = false;
                $cellContent = '';
                $timeOffHours = 0;

                foreach ($entriesForWeek as $entry) {
                    if($entry['is_timeoff']) {
                        $hasTimeOff = true;
                        $timeOffHours += $entry['assigned_hours'];
                    } else {
                        $cellContent .= "<span class='badge bg-primary mt-1'>{$entry['client_name']} ({$entry['assigned_hours']})</span><br>";
                    }
                }
            ?>
                <td class="<?php echo $hasTimeOff ? 'timeoff-cell' : ''; ?>">
                    <?php if($hasTimeOff) echo "<span class='timeoff-corner'>{$timeOffHours}</span>"; ?>
                    <?php echo $cellContent ?: "<span class='text-muted'>+</span>"; ?>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
