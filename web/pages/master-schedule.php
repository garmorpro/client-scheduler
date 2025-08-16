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
    SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name, role 
    FROM users 
    WHERE status = 'active' AND role IN ('staff', 'senior')
    ORDER BY CASE WHEN role = 'senior' THEN 1 WHEN role = 'staff' THEN 2 END, first_name ASC
";
$userResult = $conn->query($userQuery);
if ($userResult) {
    while ($userRow = $userResult->fetch_assoc()) {
        $employees[$userRow['user_id']] = [
            'full_name' => $userRow['full_name'],
            'role' => $userRow['role']
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

// Entries
$startDate = date('Y-m-d', $startMonday);
$endDate = date('Y-m-d', strtotime('+26 weeks', $startMonday));

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
        'is_timeoff' => (int)$row['is_timeoff'],
    ];
}
$stmt->close();

// Dropdown query
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

// FETCH GLOBAL TIME OFF
$globalTimeOffQuery = "SELECT week_start, assigned_hours FROM time_off WHERE is_global_timeoff = 1 AND week_start BETWEEN ? AND ?";
$stmt2 = $conn->prepare($globalTimeOffQuery);
$stmt2->bind_param('ss', $startDate, $endDate);
$stmt2->execute();
$result2 = $stmt2->get_result();

$globalTimeOff = [];
while ($row = $result2->fetch_assoc()) {
    $globalTimeOff[$row['week_start']] = $row['assigned_hours'];
}
$stmt2->close();
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

    <!-- upper search and week range selector -->
        <div class="bg-white border rounded p-4 mb-4">
            <form id="filterForm" method="get" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search employees..." onkeyup="filterEmployees()" />
                </div>
                <!-- <div class="col-md-6 d-flex justify-content-end align-items-center">
                    <a href="?week_offset=<?php echo $weekOffset - 1; ?>" 
                       class="btn btn-outline-secondary btn-sm me-2" style="border-color: rgb(229,229,229);"><i class="bi bi-chevron-left"></i></a>
        
                    <span class="fw-semibold"><?php echo $rangeLabel; ?></span>
        
                    <a href="?week_offset=<?php echo $weekOffset + 1; ?>" 
                       class="btn btn-outline-secondary btn-sm ms-2" style="border-color: rgb(229,229,229);"><i class="bi bi-chevron-right"></i></a>
                </div> -->
            </form>
        </div>
    <!-- end upper search and week range selector -->

    <!-- Master Schedule table -->
        <?php
        // Find current week index for highlight (keep same)
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
                        $globalHours = $globalTimeOff[$weekKey] ?? null;
                        $isGlobalWeek = $globalHours !== null;
                        $isCurrentWeek = ($idx === $currentWeekIndex);
                                            
                        // Combine classes
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
        <tr>
            <td class="text-start employee-name">
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
    
    $hasPersonalTimeOff = false;
    $hasTimeOffHighlight = false; // separate flag for styling
    $timeOffHours = 0;
    $cellContent = '';

    // 1️⃣ Sum employee-specific time off hours
    foreach ($entriesForWeek as $entry) {
        if (!empty($entry['is_timeoff']) && intval($entry['is_timeoff']) === 1) {
            $hasPersonalTimeOff = true;
            $timeOffHours += floatval($entry['assigned_hours']);
        }
    }

    // 2️⃣ Highlight if either personal or global time off exists
    if ($hasPersonalTimeOff || isset($globalTimeOff[$weekKey])) {
        $hasTimeOffHighlight = true;
    }

    // 3️⃣ Only add global hours to total if personal time off exists
    if ($hasPersonalTimeOff && isset($globalTimeOff[$weekKey])) {
        $timeOffHours += floatval($globalTimeOff[$weekKey]);
    }

    // 4️⃣ Build normal engagement badges (non-time-off)
    foreach ($entriesForWeek as $entry) {
        if (empty($entry['is_timeoff']) || intval($entry['is_timeoff']) !== 1) {
            $status = strtolower($entry['engagement_status'] ?? 'confirmed');
            switch ($status) {
                case 'confirmed': $entry_class='badge-confirmed'; break;
                case 'pending': $entry_class='badge-pending'; break;
                case 'not_confirmed': $entry_class='badge-not-confirmed'; break;
                default: $entry_class='badge-confirmed'; break;
            }
            $clientName = htmlspecialchars($entry['client_name']);
            $assignedHours = htmlspecialchars($entry['assigned_hours']);
            $draggableAttr = $isAdmin ? "draggable='true' class='badge badge-status $entry_class mt-1 draggable-badge'" : "class='badge badge-status $entry_class mt-1'";
            $badgeId = "badge-entry-{$entry['entry_id']}";
            $cellContent .= "<span id='{$badgeId}' {$draggableAttr} data-entry-id='{$entry['entry_id']}' data-user-id='{$userId}' data-week-start='{$weekKey}' title='Drag to move'>{$clientName} ({$assignedHours})</span>";
        }
    }

    // 5️⃣ Add plus icon if empty and admin
    if ($isAdmin && empty($cellContent) && !isset($globalTimeOff[$weekKey])) {
        $cellContent = "<i class='bi bi-plus text-muted'></i>";
    }

    // 6️⃣ Determine cell classes
    $tdClass = 'addable';
    if ($hasTimeOffHighlight) $tdClass .= ' position-relative timeoff-cell';
?>
    <td class="<?php echo $tdClass; ?>" 
        data-user-id="<?php echo $userId; ?>" 
        data-user-name="<?php echo $fullName; ?>" 
        data-week-start="<?php echo $weekKey; ?>"
        style="cursor: <?php echo $isAdmin ? 'pointer' : 'default'; ?>; vertical-align: middle;">

        <?php
        // Show total time off hours in corner only if personal time off exists
        if ($hasPersonalTimeOff) {
            echo "<span class='timeoff-corner'>{$timeOffHours}</span>";
        }
        echo $cellContent;
        ?>
    </td>
<?php endforeach; ?>

        </tr>
    <?php endforeach; ?>
</tbody>

        </table>
    </div>


    <!-- end master schedule table -->

    <!-- Custom context menu -->
<div id="badgeContextMenu" style="position:absolute; display:none; z-index:9999; background:#fff; border:1px solid #ccc; border-radius:4px; box-shadow:0 2px 6px rgba(0,0,0,0.2);">
    <ul style="list-style:none; margin:0; padding:5px 0;">
        <li id="deleteBadge" style="padding:5px 15px; cursor:pointer;">Delete Entry</li>
    </ul>
</div>

    <?php if ($isAdmin): ?>
        <?php //include_once '../includes/modals/manage_entries_prompt.php'; ?>
        <?php //include_once '../includes/modals/manage_entries.php'; ?>
        <?php //include_once '../includes/modals/editEntryModal.php'; ?>
        <?php //include_once '../includes/modals/add_entry.php'; ?>
        <?php //include_once '../includes/modals/add_engagement.php'; ?>
    <?php endif; ?>

    <?php //include_once '../includes/modals/engagement_details.php'; ?>
    <?php include_once '../includes/modals/user_details.php'; ?>
    <?php include_once '../includes/modals/viewProfileModal.php'; ?>
    <?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>
    
    <script src="../assets/js/dynamic_cell_input.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/drag_drop_function.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/delete_custom_menu.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/timeoff_menu.js?v=<?php echo time(); ?>"></script>
    
    <!-- <script src="../assets/js/open_modal.js?v=<?php echo time(); ?>"></script> -->
    <!-- <script src="../assets/js/add_entry_modal.js?v=<?php echo time(); ?>"></script> -->
    <!-- <script src="../assets/js/view_engagement_details.js?v=<?php echo time(); ?>"></script> -->
    <script src="../assets/js/number_of_weeks.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/search.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/client_dropdown.js?v=<?php echo time(); ?>"></script>
    <!-- <script src="../assets/js/dynamic_add_modal.js?v=<?php echo time(); ?>"></script> -->
    <!-- <script src="../assets/js/dynamic_manage_modal.js?v=<?php echo time(); ?>"></script> -->
    <!-- <script src="../assets/js/manage_entry_modal.js?v=<?php echo time(); ?>"></script> -->
    <script src="../assets/js/show_entries.js?v=<?php echo time(); ?>"></script>
    <!-- <script src="../assets/js/edit_modal.js?v=<?php echo time(); ?>"></script> -->
    <script src="../assets/js/delete_entry.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/view_entry_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/viewUserModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/filter_employees.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>



    <!-- Drag & Drop handler (inline to ensure full code is present) -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div>
</body>
</html>
