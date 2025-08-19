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
    SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name, role, email
    FROM users 
    WHERE status = 'active' AND role IN ('staff', 'senior', 'manager')
    ORDER BY CASE 
                WHEN role = 'senior' THEN 1 
                WHEN role = 'staff' THEN 2 
                WHEN role = 'manager' THEN 3 
             END, first_name ASC
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
      .timeoff-cell { background-color: rgb(217,217,217) !important; }
      .timeoff-current-week {background-color: rgb(217,217,217) !important; outline: 3px solid rgb(169,205,83); outline-offset: -3px;}
      <?php if ($isAdmin): ?>
      .timeoff-cell:hover { background-color: rgb(225, 225, 225) !important; }
      <?php endif; ?>
      .timeoff-corner { 
        position: absolute; top: 2px; right: 6px; font-size: 8px; font-weight: 800;
        color: rgb(50,107,61) !important;
      }
      .timeoff-card { border: 2px dashed rgb(209,226, 159) !important; background: rgb(246, 249, 236) !important; }
      .draggable-badge { cursor: grab; user-select: none; }
      .draggable-badge.dragging { opacity: 0.5; transform: scale(0.98); }
      td.drop-target { outline: 3px dashed rgba(0,123,255,0.15); }
      td.addable:hover { background: rgba(0,0,0,0.02); }
      th:first-child, td:first-child { min-width: 250px; position: sticky !important; left: 0; background-color: #fff; z-index: 101; outline: 2px solid rgb(223, 226, 230); border-left: 2px solid rgb(223, 226, 230); box-sizing: border-box; }
      .table-responsive { outline: 2px solid rgb(223, 226, 230); outline-offset: -2px; }
      .week { min-width: 200px; }
    </style>
    <script>
      const entries = <?php echo json_encode($entries); ?>;
      const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
      const GLOBAL_TIMEOFF = <?php echo json_encode($globalTimeOff); ?>;
    </script>
</head>
<body class="d-flex">
<?php include_once '../templates/sidebar.php'; ?>
<div class="flex-grow-1 p-4" style="margin-left: 250px; width: 1200px;">

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
        <div class="bg-white border rounded p-4 mb-4 d-flex justify-content-between align-items-center">
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
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-bordered align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th class="text-start align-middle"><i class="bi bi-people me-2"></i>Employee</th>
                        <?php foreach ($mondays as $idx => $monday):
                            $weekKey = date('Y-m-d', $monday);
                            $globalHours = $globalTimeOff[$weekKey]['assigned_hours'] ?? null;
                            $isGlobalWeek = $globalHours !== null;
                            $isCurrentWeek = ($idx === $currentWeekIndex);
                            $thClasses = 'align-middle week';
                            if ($isGlobalWeek) $thClasses .= ' timeoff-cell';
                            if ($isCurrentWeek) $thClasses .= ' highlight-today';
                            if ($isGlobalWeek && $isCurrentWeek) $thClasses .= ' timeoff-current-week';
                        ?>
                        <th class="<?php echo $thClasses; ?>" style="position: relative;">
                            <?php echo date('M j', $monday); ?><br>
                            <small class="text-muted">Week of <?php echo date('n/j', $monday); ?></small>
                            <?php if ($isGlobalWeek): ?>
                                <span class="timeoff-corner"><?php echo $globalHours; ?></span>
                            <?php endif; ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                            
                <tbody id="employeesTableBody">
                <?php foreach ($employees as $userId => $employee):
                    $fullName = htmlspecialchars($employee['full_name']);
                    $role = htmlspecialchars($employee['role']);
                ?>
                <tr data-role="<?php echo strtolower($role); ?>">
                    <td class="text-start employee-name"
                        data-user-id="<?php echo $userId; ?>"
                        data-user-name="<?php echo $fullName; ?>"
                        data-role="<?php echo strtolower($role); ?>"
                        data-email="<?php echo htmlspecialchars($employee['email']); ?>">
                        <div class="d-flex align-items-center">
                                <div class="rounded-circle text-white d-flex align-items-center justify-content-center me-3"
                                     style="width: 40px; height: 40px; font-size: 14px; font-weight: 500;
                                     background-color: <?php 
                                         if (strtolower($role) === 'senior') {
                                             echo 'rgb(230,144,65)';
                                         } elseif (strtolower($role) === 'staff') {
                                             echo 'rgb(66,127,194)';
                                         } else {
                                             echo '#6c757d'; // default color if neither
                                         }
                                     ?>;">
                                  <?php
                                  $initials = '';
                                  foreach (explode(' ', $fullName) as $part) {
                                      $initials .= strtoupper(substr($part, 0, 1));
                                  }
                                  echo $initials;
                                  ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo $fullName; ?></div>
                                    <div class="text-muted text-capitalize" style="font-size: 12px;"><?php echo $role; ?></div>
                                </div>
                            </div>
                    </td>
                              
                    <?php foreach ($mondays as $idx => $monday):
                        $weekKey = date('Y-m-d', $monday);
                        $entriesForWeek = $entries[$userId][$weekKey] ?? [];
                        $timeOffForWeek = $individualTimeOff[$userId][$weekKey] ?? [];
                    
                        $hasPersonalTimeOff = !empty($timeOffForWeek);
                        $timeOffHours = 0;
                        $cellContent = '';
                    
                        if ($hasPersonalTimeOff) {
                            foreach ($timeOffForWeek as $to) {
                                $timeOffHours += floatval($to['assigned_hours']);
                            }
                        }
                    
                        if (isset($globalTimeOff[$weekKey]) && $hasPersonalTimeOff) {
                            $timeOffHours += floatval($globalTimeOff[$weekKey]['assigned_hours']);
                        }
                    
                        // Build engagement badges
                        foreach ($entriesForWeek as $entry) {
                            $status = strtolower($entry['engagement_status'] ?? 'confirmed');
                            switch ($status) {
                                case 'confirmed': $entry_class='badge-confirmed'; break;
                                case 'pending': $entry_class='badge-pending'; break;
                                case 'not_confirmed': $entry_class='badge-not-confirmed'; break;
                                default: $entry_class='badge-confirmed'; break;
                            }
                            $clientName = htmlspecialchars($entry['client_name']);
                            $assignedHours = htmlspecialchars($entry['assigned_hours']);
                            $engagementId = htmlspecialchars($entry['engagement_id'] ?? '');
                            $draggableAttr = $isAdmin ? "draggable='true' class='badge badge-status $entry_class mt-1 draggable-badge'" : "class='badge badge-status $entry_class mt-1'";
                            $badgeId = "badge-entry-{$entry['entry_id']}";
                            $cellContent .= "<span id='{$badgeId}' {$draggableAttr} data-entry-id='{$entry['entry_id']}' data-user-id='{$userId}' data-engagement-id='{$engagementId}' data-week-start='{$weekKey}' title='Drag to move'>{$clientName} ({$assignedHours})</span>";
                        }
                    
                        if ($isAdmin && empty($cellContent) && !isset($globalTimeOff[$weekKey])) {
                            $cellContent = "<i class='bi bi-plus text-muted'></i>";
                        }
                    
                        $tdClass = 'addable';
                        if ($hasPersonalTimeOff || isset($globalTimeOff[$weekKey])) $tdClass .= ' position-relative timeoff-cell';
                    ?>
                    <td class="<?php echo $tdClass; ?>" 
                        data-user-id="<?php echo $userId; ?>" 
                        data-user-name="<?php echo $fullName; ?>" 
                        data-week-start="<?php echo $weekKey; ?>"
                        style="cursor: <?php echo $isAdmin ? 'pointer' : 'default'; ?>; vertical-align: middle;">
                        <?php if ($hasPersonalTimeOff) echo "<span class='timeoff-corner'>{$timeOffHours}</span>"; ?>
                        <?php echo $cellContent; ?>
                    </td>
                    <?php endforeach; ?>
                    
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>


    <!-- end master schedule table -->


    <!-- Employee Modal -->
        <div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <!-- <h5 class="modal-title" id="employeeModalLabel">Employee Info</h5> -->
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" style="margin-top: -25px !important;">
                <div id="employeeModalContent">
                  <!-- Dynamic content goes here -->
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
    <!-- end employee modal -->


<script>
document.addEventListener('DOMContentLoaded', () => {
    const employeeCells = document.querySelectorAll('td.employee-name');
    const modalEl = document.getElementById('employeeModal');
    const modal = new bootstrap.Modal(modalEl);
    const modalContent = document.getElementById('employeeModalContent');

    // Master list of all clients
    const allClients = Array.from(document.querySelectorAll('td[data-client]'))
        .map(td => td.dataset.client)
        .filter((v, i, a) => a.indexOf(v) === i);

    // Normalize a date string to Monday of that week
    function getMonday(dateStr) {
        const d = new Date(dateStr);
        const day = d.getDay(); // 0 = Sunday, 1 = Monday
        const diff = day === 0 ? -6 : 1 - day;
        d.setDate(d.getDate() + diff);
        return d.toISOString().slice(0,10); // YYYY-MM-DD
    }

    // Fetch global time off from server
    let globalTimeOffMap = {};
    async function fetchGlobalTimeOff() {
    try {
        const res = await fetch('/ajax/get_global_pto.php'); // Your PHP file
        const data = await res.json();

        globalTimeOffMap = {};
        data.forEach(item => {
            const mondayWeek = getMonday(item.week_start);
            globalTimeOffMap[mondayWeek] = parseFloat(item.assigned_hours) || 0;
        });

        console.log("Global Time Off Map:", globalTimeOffMap);
    } catch(err) {
        console.error("Failed to fetch global time off:", err);
    }
}

    employeeCells.forEach(td => {
        td.style.cursor = 'pointer';
        td.addEventListener('click', async () => {
            const userName = td.dataset.userName;
            const role = td.dataset.role || td.querySelector('.text-muted')?.textContent || 'staff';
            const email = td.dataset.email || '';
            if (!userName) return;

            const initials = userName.split(' ').map(p => p[0].toUpperCase()).join('');

            // Ensure global PTO is loaded before opening modal
            await fetchGlobalTimeOff();

            const row = td.closest('tr');
            const weekTds = Array.from(row.querySelectorAll('td.addable'));
            let allAssignments = [];
            let totalHours = 0;
            const uniqueEngagements = new Set();

            const timeOffMap = {};

            // Collect global and personal PTO only for weeks present in the table
            weekTds.forEach(weekTd => {
                const weekStartRaw = weekTd.dataset.weekStart;
                const weekStart = getMonday(weekStartRaw);
                const globalHours = globalTimeOffMap[weekStart] || 0;

                const timeOffCorner = weekTd.querySelector('.timeoff-corner');
                const personalHours = timeOffCorner ? parseFloat(timeOffCorner.textContent) || 0 : 0;
                const totalWeekHours = globalHours + personalHours;

                if (totalWeekHours > 0) {
                    timeOffMap[weekStart] = totalWeekHours;
                }

                console.log(`Week ${weekStart} for ${userName}: global=${globalHours}, personal=${personalHours}, total=${totalWeekHours}`);

                // Assignments
                const badges = Array.from(weekTd.querySelectorAll('.draggable-badge'));
                badges.forEach(b => {
                    const match = b.textContent.match(/\(([\d.]+)\)/);
                    const hours = match ? parseFloat(match[1]) : 0;
                    const clientName = b.textContent.split('(')[0].trim();
                    const engagementId = b.dataset.engagementId;
                    const statusMatch = b.className.match(/badge-(confirmed|pending|not-confirmed)/);
                    const statusClass = statusMatch ? statusMatch[1] : 'not-confirmed';
                    allAssignments.push({clientName, hours, status: statusClass, weekStart, engagementId});
                    totalHours += hours;
                    if (engagementId) uniqueEngagements.add(engagementId);
                });
            });

            console.log(`Final timeOffMap for ${userName}:`, timeOffMap);

            const timeOffWeeks = Object.entries(timeOffMap)
                .map(([week, hours]) => ({ week, hours }))
                .sort((a,b) => new Date(a.week) - new Date(b.week));

            const totalTimeOffHours = timeOffWeeks.reduce((sum, w) => sum + w.hours, 0);

            // Initialize clients map
            const clientsMap = {};
            allClients.forEach(client => {
                clientsMap[client] = { total: 0, status: 'not-confirmed', weeks: [] };
            });

            allAssignments.forEach(a => {
                if (!clientsMap[a.clientName]) clientsMap[a.clientName] = { total:0, status:a.status, weeks:[] };
                clientsMap[a.clientName].total += a.hours;
                clientsMap[a.clientName].weeks.push({ week: a.weekStart, hours: a.hours });
                clientsMap[a.clientName].status = a.status;
            });

            const avgHoursPerWeek = allAssignments.length > 0 ? (totalHours / allAssignments.length).toFixed(1) : 0;

            // Build modal HTML
            let html = `<div class="d-flex align-items-center mb-3">
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center me-3"
                     style="width:50px;height:50px;font-size:18px;font-weight:500;
                     background-color:${role.toLowerCase() === 'senior' ? 'rgb(230,144,65)' :
                        role.toLowerCase() === 'staff' ? 'rgb(66,127,194)' : '#6c757d'};">
                    ${initials}
                </div>
                <div>
                    <div class="fw-semibold">${userName}</div>
                    <div class="text-muted text-capitalize">${role} <i class="bi bi-dot ms-1 me-1"></i>
                        <span class="small text-lowercase">${email}</span>
                    </div>
                </div>
            </div>

            <div class="mb-3 d-flex gap-3">
                <div class="card flex-fill d-flex" style="border-left: 4px solid rgb(68,125,252);">
                    <div class="card-body w-100 d-flex justify-content-between align-items-center p-3">
                        <div>
                            <small class="text-muted" style="font-size: 14px !important;">Total Engagements</small>
                            <div class="fw-semibold fs-4" style="color: rgb(68,125,252);">${uniqueEngagements.size}</div>
                        </div>
                        <div class="rounded-circle d-flex justify-content-center align-items-center" style="width:40px; height:40px; background-color: rgb(222,234,253);">
                            <i class="bi bi-building" style="color: rgb(68,125,252);"></i>
                        </div>
                    </div>
                </div>

                <div class="card flex-fill d-flex" style="border-left: 4px solid rgb(79,197,95);">
                    <div class="card-body w-100 d-flex justify-content-between align-items-center p-3">
                        <div>
                            <small class="text-muted" style="font-size: 14px !important;">Total Hours</small>
                            <div class="fw-semibold fs-4" style="color: rgb(79,197,95);">${totalHours}</div>
                        </div>
                        <div class="rounded-circle d-flex justify-content-center align-items-center" style="width:40px; height:40px; background-color: rgb(226,251,232);">
                            <i class="bi bi-people" style="color: rgb(79,197,95)"></i>
                        </div>
                    </div>
                </div>

                <div class="card flex-fill d-flex" style="border-left: 4px solid rgb(161,77,253);">
                    <div class="card-body w-100 d-flex justify-content-between align-items-center p-3">
                        <div>
                            <small class="text-muted" style="font-size: 14px !important;">Avg Hours/Entry</small>
                            <div class="fw-semibold fs-4" style="color: rgb(161,77,253);">${avgHoursPerWeek}</div>
                        </div>
                        <div class="rounded-circle d-flex justify-content-center align-items-center" style="width:40px; height:40px; background-color: rgb(241,232,253);">
                            <i class="bi bi-people" style="color: rgb(161,77,253);"></i>
                        </div>
                    </div>
                </div>
            </div>`;

            html += `<div class="border rounded p-3 mb-3">
                <ul class="list-group">
                    <li class="list-group-item d-flex fw-semibold text-muted bg-light">
                        <div class="col-6">Client Name</div>
                        <div class="col-2 text-center">Total Hours</div>
                        <div class="col-4">Week Assignments / Time Off</div>
                    </li>`;

            html += `
                <li class="list-group-item d-flex align-items-center text-truncate">
                    <div class="col-6 fw-semibold text-black">Time Off</div>
                    <div class="col-2 text-center">${totalTimeOffHours}</div>
                    <div class="col-4 d-flex flex-wrap gap-1">
                        ${timeOffWeeks.map(w => `
                            <div style="background-color:#f5f5f5; padding:4px; min-width:50px; text-align:center; border-radius:4px; font-size:12px;">
                                ${new Date(w.week).toLocaleDateString('en-US', {month:'short', day:'numeric'})}<br>
                                <span class="fw-semibold text-black">${w.hours}h</span>
                            </div>
                        `).join('')}
                    </div>
                </li>`;

            Object.entries(clientsMap).forEach(([clientName, info]) => {
                html += `
                    <li class="list-group-item d-flex align-items-center text-truncate">
                        <div class="col-6 text-truncate">
                            <span class="fs-6 fw-semibold text-black">${clientName}</span> 
                            <span class="badge badge-status badge-${info.status} ms-1 text-capitalize">
                                ${info.status === 'not-confirmed' ? 'not confirmed' : info.status}
                            </span>
                        </div>
                        <div class="col-2 text-center">
                           <span class="fs-5 fw-semibold text-black">${info.total}</span><br>
                            <span class="text-muted" style="font-size: 10px;">hours</span>
                        </div>
                        <div class="col-4 d-flex flex-wrap gap-1">
                            ${info.weeks.map(w => `
                                <div style="background-color:#f5f5f5; padding:4px; min-width:50px; text-align:center; border-radius:4px; font-size:12px;">
                                    ${new Date(w.week).toLocaleDateString('en-US', {month:'short', day:'numeric'})}<br>
                                    <span class="fw-semibold text-black">${w.hours}h</span>
                                </div>
                            `).join('')}
                        </div>
                    </li>`;
            });

            html += `</ul></div>`;
            modalContent.innerHTML = html;
            modal.show();
        });
    });
});
</script>

















    <?php include_once '../includes/modals/user_details.php'; ?>
    <?php include_once '../includes/modals/viewProfileModal.php'; ?>
    <?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>
    
    <script src="../assets/js/dynamic_cell_input.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/drag_drop_function.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/delete_custom_menu.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/timeoff_menu.js?v=<?php echo time(); ?>"></script>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div>
</body>
</html>
