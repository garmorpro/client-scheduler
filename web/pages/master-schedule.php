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

// Individual Time Off
$individualTimeOffQuery = "
    SELECT timeoff_id, user_id, week_start, assigned_hours, timeoff_note
    FROM time_off 
    WHERE is_global_timeoff = 0 AND week_start BETWEEN ? AND ?
";
$stmtTimeOff = $conn->prepare($individualTimeOffQuery);
$stmtTimeOff->bind_param('ss', $startDate, $endDate);
$stmtTimeOff->execute();
$resultTimeOff = $stmtTimeOff->get_result();

$individualTimeOff = [];
while ($row = $resultTimeOff->fetch_assoc()) {
    $individualTimeOff[$row['user_id']][$row['week_start']][] = [
        'timeoff_id' => $row['timeoff_id'],
        'assigned_hours' => $row['assigned_hours'],
        'timeoff_note' => $row['timeoff_note']
    ];
}
$stmtTimeOff->close();

// Global Time Off
$globalTimeOffQuery = "
    SELECT week_start, assigned_hours, timeoff_note
    FROM time_off
    WHERE is_global_timeoff = 1 AND week_start BETWEEN ? AND ?
";
$stmt2 = $conn->prepare($globalTimeOffQuery);
$stmt2->bind_param('ss', $startDate, $endDate);
$stmt2->execute();
$result2 = $stmt2->get_result();

$globalTimeOff = [];
while ($row = $result2->fetch_assoc()) {
    $globalTimeOff[$row['week_start']] = [
        'assigned_hours' => $row['assigned_hours'],
        'note' => $row['timeoff_note']
    ];
}
$stmt2->close();

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
    <!-- ... all your CSS & JS includes remain the same ... -->

    <script>
      const entries = <?php echo json_encode($entries); ?>;
      const INDIVIDUAL_TIMEOFF = <?php echo json_encode($individualTimeOff); ?>;
      const GLOBAL_TIMEOFF = <?php echo json_encode($globalTimeOff); ?>;
      const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    </script>
</head>
<body class="d-flex">
<?php include_once '../templates/sidebar.php'; ?>
<div class="flex-grow-1 p-4" style="margin-left: 250px; width: 1200px;">
    <!-- ... header & filters remain unchanged ... -->

    <!-- Master Schedule table -->
    <div class="table-responsive" style="overflow-x: auto;">
    <table class="table table-bordered align-middle text-center">
        <thead class="table-light">
            <tr>
                <th class="text-start align-middle"><i class="bi bi-people me-2"></i>Employee</th>
                <?php foreach ($mondays as $idx => $monday):
                    $weekKey = date('Y-m-d', $monday);
                    $globalHours = GLOBAL_TIMEOFF[$weekKey]['assigned_hours'] ?? null;
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
        <tr>
            <td class="text-start employee-name">
                <!-- ... employee avatar & name same as before ... -->
            </td>

            <?php foreach ($mondays as $idx => $monday):
                $weekKey = date('Y-m-d', $monday);
                $entriesForWeek = $entries[$userId][$weekKey] ?? [];
                $timeOffForWeek = INDIVIDUAL_TIMEOFF[$userId][$weekKey] ?? [];

                $hasPersonalTimeOff = !empty($timeOffForWeek);
                $timeOffHours = 0;
                $cellContent = '';

                if ($hasPersonalTimeOff) {
                    foreach ($timeOffForWeek as $to) {
                        $timeOffHours += floatval($to['assigned_hours']);
                    }
                }

                if (isset(GLOBAL_TIMEOFF[$weekKey]) && $hasPersonalTimeOff) {
                    $timeOffHours += floatval(GLOBAL_TIMEOFF[$weekKey]['assigned_hours']);
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
                    $draggableAttr = $isAdmin ? "draggable='true' class='badge badge-status $entry_class mt-1 draggable-badge'" : "class='badge badge-status $entry_class mt-1'";
                    $badgeId = "badge-entry-{$entry['entry_id']}";
                    $cellContent .= "<span id='{$badgeId}' {$draggableAttr} data-entry-id='{$entry['entry_id']}' data-user-id='{$userId}' data-week-start='{$weekKey}' title='Drag to move'>{$clientName} ({$assignedHours})</span>";
                }

                if ($isAdmin && empty($cellContent) && !isset(GLOBAL_TIMEOFF[$weekKey])) {
                    $cellContent = "<i class='bi bi-plus text-muted'></i>";
                }

                $tdClass = 'addable';
                if ($hasPersonalTimeOff || isset(GLOBAL_TIMEOFF[$weekKey])) $tdClass .= ' position-relative timeoff-cell';
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
