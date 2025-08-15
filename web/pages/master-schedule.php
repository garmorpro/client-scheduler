<?php
require_once '../includes/db.php';
session_start();

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isManager = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'manager';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Function to calculate mondays based on offset
function getMondays($weekOffset = 0, $weeksToShow = 7) {
    $today = strtotime('today');
    $currentMonday = strtotime('monday this week', $today);
    $startMonday = strtotime("-2 weeks", $currentMonday);
    $startMonday = strtotime("+{$weekOffset} weeks", $startMonday);

    $mondays = [];
    for ($i = 0; $i < $weeksToShow; $i++) {
        $mondays[] = strtotime("+{$i} weeks", $startMonday);
    }
    return $mondays;
}

// Default mondays for initial page load
$weekOffset = 0;
$mondays = getMondays($weekOffset);

// Get employees
$employees = [];
$userQuery = "
    SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name, role 
    FROM users 
    WHERE status='active' AND role IN ('staff','senior')
    ORDER BY CASE WHEN role='senior' THEN 1 WHEN role='staff' THEN 2 END, first_name ASC
";
$userResult = $conn->query($userQuery);
while ($userRow = $userResult->fetch_assoc()) {
    $employees[$userRow['user_id']] = [
        'full_name' => $userRow['full_name'],
        'role' => $userRow['role']
    ];
}

// Get clients (for dropdowns)
$clientQuery = "SELECT engagement_id, client_name FROM engagements";
$clientResult = $conn->query($clientQuery);
$activeClients = [];
while ($clientRow = $clientResult->fetch_assoc()) {
    $activeClients[] = $clientRow;
}

// Calculate range label
$firstWeek = reset($mondays);
$lastWeek = end($mondays);
$rangeLabel = "Week of " . date('n/j', $firstWeek) . " - Week of " . date('n/j', $lastWeek);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Master Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">

    <style>
        .timeoff-cell { background-color: rgb(217,217,217) !important; }
        <?php if ($isAdmin): ?>
        .timeoff-cell:hover { background-color: #e0f7fa !important; }
        <?php endif; ?>
        .timeoff-corner { position: absolute; top: 2px; right: 6px; font-size: .50rem; }
        .timeoff-card { border: 2px dashed rgb(209,226,159) !important; background: rgb(246,249,236) !important; }
        .highlight-today { background-color: #fff3cd !important; }
    </style>
</head>
<body class="d-flex">
<?php include_once '../templates/sidebar.php'; ?>
<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">Master Schedule</h3>
            <p class="text-muted mb-0">Complete overview of all client engagements and team assignments</p>
        </div>
        <div class="header-buttons">
            <a href="#" onclick="location.reload();" class="badge text-black p-2 text-decoration-none fw-medium me-1" style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
              <i class="bi bi-arrow-clockwise me-3"></i>Refresh
            </a>
        </div>
    </div>

    <!-- Search and dynamic week slider -->
    <div class="bg-white border rounded p-4 mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-6">
                <input type="text" id="searchInput" class="form-control" placeholder="Search employees..." onkeyup="filterEmployees()" />
            </div>
            <div class="col-md-6 d-flex justify-content-end align-items-center gap-3">
                <input type="range" id="weekSlider" min="-2" max="10" value="0" style="width:200px;">
                <span id="weekLabel" class="fw-semibold"><?php echo $rangeLabel; ?></span>
            </div>
        </div>
    </div>

    <!-- Master Schedule Table -->
    <div class="table-responsive" id="masterScheduleContainer">
        <table class="table table-bordered align-middle text-center" id="masterScheduleTable">
            <thead class="table-light">
                <tr>
                    <th class="text-start align-middle"><i class="bi bi-people me-2"></i>Employee</th>
                    <?php foreach ($mondays as $monday): ?>
                        <th><?php echo date('M j', $monday); ?><br><small class="text-muted">Week of <?php echo date('n/j', $monday); ?></small></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="employeesTableBody">
                <?php foreach ($employees as $userId => $employee): ?>
                    <tr data-user-id="<?php echo $userId; ?>">
                        <td class="text-start employee-name">
                            <?php echo htmlspecialchars($employee['full_name']); ?> (<?php echo htmlspecialchars($employee['role']); ?>)
                        </td>
                        <?php foreach ($mondays as $monday): ?>
                            <td class="timeoff-cell" data-week="<?php echo date('Y-m-d',$monday); ?>">Loading...</td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($isAdmin): ?>
        <?php include_once '../includes/modals/manage_entries_prompt.php'; ?>
        <?php include_once '../includes/modals/manage_entries.php'; ?>
        <?php include_once '../includes/modals/editEntryModal.php'; ?>
        <?php include_once '../includes/modals/add_entry.php'; ?>
        <?php include_once '../includes/modals/add_engagement.php'; ?>
    <?php endif; ?>

    <?php include_once '../includes/modals/engagement_details.php'; ?>
    <?php include_once '../includes/modals/user_details.php'; ?>
    <?php include_once '../includes/modals/viewProfileModal.php'; ?>
    <?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const weekSlider = document.getElementById('weekSlider');
const weekLabel = document.getElementById('weekLabel');

function fetchSchedule(weekOffset){
    fetch(`ajax_fetch_schedule.php?week_offset=${weekOffset}`)
        .then(res => res.json())
        .then(data => {
            // Update table headers and cells dynamically
            const mondays = data.mondays;
            const tbody = document.getElementById('employeesTableBody');

            // Update header
            const table = document.getElementById('masterScheduleTable');
            const thead = table.querySelector('thead tr');
            thead.innerHTML = '<th class="text-start align-middle"><i class="bi bi-people me-2"></i>Employee</th>';
            mondays.forEach(m => {
                const th = document.createElement('th');
                th.innerHTML = `${m.label}<br><small class="text-muted">${m.range}</small>`;
                thead.appendChild(th);
            });

            // Update cells
            tbody.querySelectorAll('tr').forEach(tr => {
                const userId = tr.dataset.userId;
                tr.querySelectorAll('td:not(:first-child)').forEach((td, idx) => {
                    const weekKey = mondays[idx].date;
                    const entries = data.entries[userId]?.[weekKey] || [];
                    td.innerHTML = entries.map(e => `${e.client_name} (${e.assigned_hours})`).join('<br>') || '<span class="text-muted">+</span>';
                });
            });

            // Update label
            weekLabel.textContent = data.rangeLabel;
        });
}

// Initialize
fetchSchedule(weekSlider.value);

weekSlider.addEventListener('input', () => {
    weekLabel.textContent = `Loading...`;
});

weekSlider.addEventListener('change', () => {
    fetchSchedule(weekSlider.value);
});
</script>

</body>
</html>
