<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';

$today = strtotime('today');

// Calculate Mondays for horizontal display
$currentMonday = strtotime('monday this week', $today);
$weekOffset = isset($_GET['week_offset']) ? intval($_GET['week_offset']) : 0;
$startMonday = strtotime("-2 weeks +{$weekOffset} weeks", $currentMonday);

$weeksToShow = 24;
$mondays = [];
for ($i = 0; $i < $weeksToShow; $i++) {
    $mondays[] = strtotime("+{$i} weeks", $startMonday);
}
$firstWeek = reset($mondays);
$lastWeek = end($mondays);
$rangeLabel = "Week of " . date('n/j', $firstWeek) . " - Week of " . date('n/j', $lastWeek);

// Get employees
$employees = [];
$userQuery = "
    SELECT user_id, CONCAT(first_name,' ',last_name) AS full_name, role 
    FROM users 
    WHERE status='active' AND role IN ('staff','senior')
    ORDER BY FIELD(role,'senior','staff'), first_name
";
$userResult = $conn->query($userQuery);
while ($row = $userResult->fetch_assoc()) {
    $employees[$row['user_id']] = ['full_name' => $row['full_name'], 'role' => $row['role']];
}

// Fetch entries including time-off
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
        /* Scrollable spreadsheet table */
        #scheduleContainer {
            overflow-x: auto;
            overflow-y: auto;
            max-width: calc(140px * 10);
            max-height: 600px;
            white-space: nowrap;
        }
        table { min-width: max-content; border-collapse: collapse; }
        th, td { border: 1px solid #dee2e6; padding: 0.5rem; text-align: center; }
        thead th { position: sticky; top: 0; background: #f8f9fa; z-index: 10; }
        th:first-child, td:first-child { position: sticky; left: 0; background: #fff; z-index: 20; text-align: left; }
        thead th:first-child { z-index: 30; }
        .timeoff-cell { background-color: #d9d9d9; position: relative; }
        .timeoff-corner { position: absolute; top: 2px; right: 6px; font-size: 0.7rem; color: red; font-weight: bold; }
        .badge-status { display: block; margin: 2px 0; font-size: 0.75rem; padding: 0.25em 0.4em; }
        .badge-confirmed { background-color: #d1e2ff; color: #004085; }
        .badge-pending { background-color: #fff3cd; color: #856404; }
        .badge-not-confirmed { background-color: #f8d7da; color: #721c24; }
        .employee-name { display: flex; align-items: center; gap: 0.5rem; }
        .employee-initials { width: 40px; height: 40px; border-radius: 50%; background: #343a40; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 500; }
    </style>
</head>
<body class="d-flex">
<?php include_once '../templates/sidebar.php'; ?>
<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <h3>Master Schedule</h3>
    <p class="text-muted">Overview of client engagements and team assignments</p>

    <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search employees..." onkeyup="filterEmployees()">

    <div id="scheduleContainer">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Employee</th>
                    <?php foreach ($mondays as $idx => $monday):
                        $weekKey = date('Y-m-d', $monday);
                        $isCurrent = ($today >= $monday && $today < strtotime('+7 days', $monday));
                    ?>
                        <th class="<?= $isCurrent ? 'table-primary' : '' ?>">
                            <?= date('M j', $monday) ?><br><small class="text-muted">Week of <?= date('n/j', $monday) ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="employeesTableBody">
            <?php foreach ($employees as $userId => $employee):
                $fullName = htmlspecialchars($employee['full_name']);
                $nameParts = explode(' ', $fullName);
                $initials = '';
                foreach ($nameParts as $part) $initials .= strtoupper(substr($part,0,1));
            ?>
                <tr>
                    <td class="employee-name">
                        <div class="employee-initials"><?= $initials ?></div>
                        <div>
                            <div><?= $fullName ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($employee['role']) ?></div>
                        </div>
                    </td>
                    <?php foreach ($mondays as $monday):
                        $weekKey = date('Y-m-d', $monday);
                        $entriesForWeek = $entries[$userId][$weekKey] ?? [];
                        $hasTimeOff = false;
                        $timeOffHours = 0;
                        $cellContent = '';

                        foreach ($entriesForWeek as $entry) {
    if ($entry['is_timeoff']) {
        $hasTimeOff = true;
        $timeOffHours += floatval($entry['assigned_hours']);
    } else {
        $engagementStatus = strtolower($entry['engagement_status'] ?? 'confirmed');
        switch($engagementStatus) {
            case 'pending':
                $statusClass = 'badge-pending';
                break;
            case 'not_confirmed':
                $statusClass = 'badge-not-confirmed';
                break;
            default:
                $statusClass = 'badge-confirmed';
                break;
        }
        $cellContent .= "<span class='badge-status $statusClass'>{$entry['client_name']} ({$entry['assigned_hours']})</span>";
    }
}

                    ?>
                        <td class="<?= $hasTimeOff ? 'timeoff-cell' : '' ?>" <?= $isAdmin ? "data-user-id='$userId' data-week-start='$weekKey'" : "" ?>>
                            <?php if ($hasTimeOff) echo "<span class='timeoff-corner'>{$timeOffHours}</span>"; ?>
                            <?= $cellContent ?: '<span class="text-muted">-</span>' ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterEmployees() {
    const filter = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#employeesTableBody tr').forEach(row => {
        const nameCell = row.querySelector('td:first-child div div').textContent.toLowerCase();
        row.style.display = nameCell.includes(filter) ? '' : 'none';
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
