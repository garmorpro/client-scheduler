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
for ($i = 0; $i < 26; $i++) {
    $mondays[] = strtotime("+{$i} weeks", $startMonday);
}

$firstWeek = reset($mondays);
$lastWeek = end($mondays);
$rangeLabel = "Week of " . date('n/j', $firstWeek) . " - Week of " . date('n/j', $lastWeek);

// Employees
$employees = [];
$userQuery = "
    SELECT user_id, full_name, role, email
    FROM users 
    WHERE status = 'active' AND role IN ('staff', 'senior', 'manager')
    ORDER BY CASE 
                WHEN role = 'senior' THEN 1 
                WHEN role = 'staff' THEN 2 
                WHEN role = 'manager' THEN 3 
             END, full_name ASC
";
$userResult = $conn->query($userQuery);
if ($userResult) {
    while ($userRow = $userResult->fetch_assoc()) {
        $employees[$userRow['user_id']] = [
            'full_name' => $userRow['full_name'],
            'role' => $userRow['role'],
            'email' => $userRow['email']
        ];
    }
}

// Clients
$clientQuery = "SELECT engagement_id, client_name FROM engagements";
$clientResult = $conn->query($clientQuery);
$activeClients = [];
while ($clientRow = $clientResult->fetch_assoc()) {
    $activeClients[] = $clientRow;
}

// Entries (non-timeoff only)
$startDate = date('Y-m-d', $startMonday);
$endDate = date('Y-m-d', strtotime('+26 weeks', $startMonday));

$query = "
    SELECT a.entry_id, a.user_id, a.engagement_id, e.client_name, a.week_start, a.assigned_hours, e.status AS engagement_status
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
    ];
}
$stmt->close();

// Global Time Off
$globalTimeOffQuery = "SELECT week_start, assigned_hours FROM time_off WHERE is_global_timeoff = 1 AND week_start BETWEEN ? AND ?";
$stmt2 = $conn->prepare($globalTimeOffQuery);
$stmt2->bind_param('ss', $startDate, $endDate);
$stmt2->execute();
$result2 = $stmt2->get_result();

$globalTimeOff = [];
while ($row = $result2->fetch_assoc()) {
    $globalTimeOff[$row['week_start']] = [
        'assigned_hours' => $row['assigned_hours']
    ];
}
$stmt2->close();

// Individual Time Off
$individualTimeOffQuery = "SELECT user_id, week_start, assigned_hours FROM time_off WHERE is_global_timeoff = 0 AND week_start BETWEEN ? AND ?";
$stmt3 = $conn->prepare($individualTimeOffQuery);
$stmt3->bind_param('ss', $startDate, $endDate);
$stmt3->execute();
$result3 = $stmt3->get_result();

$individualTimeOff = [];
while ($row = $result3->fetch_assoc()) {
    $individualTimeOff[$row['user_id']][$row['week_start']][] = [
        'assigned_hours' => $row['assigned_hours']
    ];
}
$stmt3->close();

// Dropdown query remains the same
$dropdownquery = "
  SELECT e.engagement_id, e.client_name, e.status, e.budgeted_hours, IFNULL(SUM(a.assigned_hours), 0) AS assigned_hours
  FROM engagements e
  LEFT JOIN entries a ON a.engagement_id = e.engagement_id
  GROUP BY e.engagement_id
  ORDER BY e.client_name
";
$dropdownresult = $conn->query($dropdownquery);
$clientsWithHours = [];
while ($D_row = $dropdownresult->fetch_assoc()) {
  $clientsWithHours[] = $D_row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Master Schedule</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
     /* ========= GENERAL ========= */
.schedule-wrapper {
  display: grid;
  grid-template-columns: 260px 1fr;
  grid-template-rows: auto 1fr;
  height: calc(100vh - 260px);
  border: 1px solid #ddd;
  border-radius: 10px;
}

.schedule-header {
  display: grid;
  grid-template-columns: 260px 1fr;
  position: sticky;
  top: 0;
  background: #fff;
  z-index: 10;
}

.employee-header {
  border-right: 2px solid #ddd;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.weeks-header {
  display: flex;
  overflow-x: auto;
}

.schedule-body {
  display: grid;
  grid-template-columns: 260px 1fr;
  height: 100%;
}

.employee-column {
  overflow-y: auto;
  border-right: 2px solid #ddd;
}

.weeks-content {
  overflow: auto;
  display: grid;
  grid-template-columns: repeat(26, 150px);
  gap: 2px;
}
.week-cell {
  border: 1px solid #eee;
  min-height: 60px;
  padding: 2px;
  position: relative;
}
.badge {
  display: block;
  margin: 2px 0;
  padding: 2px 4px;
  border-radius: 4px;
  font-size: 12px;
  cursor: grab;
}
    </style>
    <script>
      const entries = <?php echo json_encode($entries); ?>;
      const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
      const GLOBAL_TIMEOFF = <?php echo json_encode($globalTimeOff); ?>;
    </script>
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">
<?php include_once '../templates/sidebar.php'; ?>
<div class="main-content p-4">

    <!-- header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Master Schedule</h3>
                <p class="text-muted mb-0">Complete overview of all client engagements and team assignments</p>
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
    <!-- end header -->

    <!-- upper search and filter -->
        <div class="rounded p-4 mb-4 d-flex justify-content-between align-items-center">
            <!-- Search bar on left -->
            <div class="flex-grow-1 me-3">
                <input type="search" id="searchInput" class="form-control w-50" placeholder="Search employees..." onkeyup="filterEmployees()" />
            </div>

            <!-- Role filter dropdown on right -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="roleFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    Filter Roles
                </button>
                <ul class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="roleFilterDropdown" style="min-width: 200px;">
                    <li>
                        <div class="form-check">
                            <input class="form-check-input role-checkbox" type="checkbox" value="staff" id="roleStaff" checked>
                            <label class="form-check-label" for="roleStaff">Staff</label>
                        </div>
                    </li>
                    <li>
                        <div class="form-check">
                            <input class="form-check-input role-checkbox" type="checkbox" value="senior" id="roleSenior" checked>
                            <label class="form-check-label" for="roleSenior">Senior</label>
                        </div>
                    </li>
                    <li>
                        <div class="form-check">
                            <input class="form-check-input role-checkbox" type="checkbox" value="manager" id="roleManager">
                            <label class="form-check-label" for="roleManager">Manager</label>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    <!-- end upper search and week range selector -->

    <!-- Master Schedule table -->
        <?php
        // Find current week index for highlight
        $currentWeekIndex = null;
        foreach ($mondays as $idx => $monday) {
            $weekStart = $monday;
            $weekEnd = strtotime('+7 days', $weekStart);
            if ($today >= $weekStart && $today < $weekEnd) {
                $currentWeekIndex = $idx;
                break;
            }
        }
        ?>
        <div class="schedule-wrapper">
  <!-- Header -->
  <div class="schedule-header">
    <div class="employee-header">Employee</div>
    <div class="weeks-header">
      <?php foreach($mondays as $monday): ?>
        <div class="week-header" style="min-width:150px">
          <?= date('M j', $monday) ?><br>
          <small class="text-muted">Week of <?= date('n/j', $monday) ?></small>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Body -->
  <div class="schedule-body">
    <!-- Employee column -->
    <div class="employee-column">
      <?php foreach($employees as $employee): ?>
        <div class="employee-name" style="height:60px; display:flex; align-items:center; padding:0 5px;">
          <?= htmlspecialchars($employee['full_name']) ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Week cells -->
    <div class="weeks-content">
      <?php foreach($employees as $userId => $employee): ?>
        <?php foreach($mondays as $monday): 
          $weekKey = date('Y-m-d', $monday);
          $entriesForWeek = $entries[$userId][$weekKey] ?? [];
          $timeOffForWeek = $individualTimeOff[$userId][$weekKey] ?? [];
        ?>
          <div class="week-cell">
            <?php foreach($entriesForWeek as $entry): ?>
              <span class="badge" style="background:lightblue;">
                <?= htmlspecialchars($entry['client_name']) ?> (<?= $entry['assigned_hours'] ?>)
              </span>
            <?php endforeach; ?>
            <?php if(!empty($timeOffForWeek)): ?>
              <span class="timeoff-corner"><?= array_sum(array_column($timeOffForWeek, 'assigned_hours')) ?></span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>


    <!-- end master schedule table -->


    <!-- Employee Modal -->
        <div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg " style="overflow-y: initial !important">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="employeeModalLabel">Employee Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" style="max-height: 70vh !important; overflow-y: auto !important;">
                <div id="employeeModalContent">
                  <!-- Dynamic content goes here -->
                </div>
              </div>
              <!-- <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div> -->
            </div>
          </div>
        </div>
    <!-- end employee modal -->



    <?php include_once '../includes/modals/user_details.php'; ?>
    <?php include_once '../includes/modals/viewProfileModal.php'; ?>
    <?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>
    
    <script src="../assets/js/dynamic_cell_input.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/drag_drop_function.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/delete_custom_menu.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/timeoff_menu.js?v=<?php echo time(); ?>"></script>
    <?php if ($isAdmin): ?>
    <script src="../assets/js/employee_details.js?v=<?php echo time(); ?>"></script>
    <?php endif; ?>
    <script src="../assets/js/filter_role.js?v=<?php echo time(); ?>"></script>
    
    <script src="../assets/js/number_of_weeks.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/search.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/client_dropdown.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/show_entries.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/delete_entry.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/view_entry_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/viewUserModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/filter_employees.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>

    <script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


       
    
</div>
</body>
</html>
