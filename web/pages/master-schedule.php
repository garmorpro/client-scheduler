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

// Calculate Mondays as timestamps (keep same)
$currentMonday = strtotime('monday this week', $today);
$weekOffset = isset($_GET['week_offset']) ? intval($_GET['week_offset']) : 0;
$startMonday = strtotime("-2 weeks", $currentMonday);
$startMonday = strtotime("+{$weekOffset} weeks", $startMonday);

// $mondays = [];
// for ($i = 0; $i < 7; $i++) {
//     $mondays[] = strtotime("+{$i} weeks", $startMonday);
// }

// $mondays = [];
// for ($i = 0; $i < 7; $i++) {
//     $mondays[] = strtotime("+{$i} weeks", $startMonday);
// }

$mondays = [];
$weeksToShow = 24; // number of weeks to display horizontally
for ($i = 0; $i < $weeksToShow; $i++) {
    $mondays[] = strtotime("+{$i} weeks", $startMonday);
}

// Range label for header (keep same)
$firstWeek = reset($mondays);
$lastWeek = end($mondays);
$rangeLabel = "Week of " . date('n/j', $firstWeek) . " - Week of " . date('n/j', $lastWeek);

// Get employees (keep same)
$employees = [];
$userQuery = "
    SELECT 
        user_id, 
        CONCAT(first_name, ' ', last_name) AS full_name, 
        role 
    FROM users 
    WHERE status = 'active' 
      AND role IN ('staff', 'senior')
    ORDER BY 
        CASE 
            WHEN role = 'senior' THEN 1
            WHEN role = 'staff' THEN 2
        END,
        first_name ASC
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

// Fetch clients (keep same)
$clientQuery = "SELECT engagement_id, client_name FROM engagements";
$clientResult = $conn->query($clientQuery);

if ($clientResult === false) {
    die('MySQL query failed: ' . $conn->error);
}

$activeClients = [];
while ($clientRow = $clientResult->fetch_assoc()) {
    $activeClients[] = $clientRow;
}

$startDate = date('Y-m-d', $startMonday);
$endDate = date('Y-m-d', strtotime('+6 weeks', $startMonday));

/**
 * FETCH ENTRIES
 * - Include time off rows as well (LEFT JOIN so entries without engagement_id are not dropped)
 * - Use is_timeoff (0/1) instead of a "type" column
 */
$query = "
    SELECT 
        a.entry_id,
        a.user_id,
        a.engagement_id,
        e.client_name,
        a.week_start,
        a.assigned_hours,
        e.status AS engagement_status,
        a.is_timeoff
    FROM 
        entries a
    LEFT JOIN 
        engagements e ON a.engagement_id = e.engagement_id
    WHERE 
        a.week_start BETWEEN ? AND ?
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

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

// Dropdown query (unchanged)
$dropdownquery = "
  SELECT 
    e.engagement_id,
    e.client_name,
    e.status,
    e.total_available_hours,
    IFNULL(SUM(a.assigned_hours), 0) AS assigned_hours
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">

    
    <script>
      const entries = <?php echo json_encode($entries); ?>;
    </script>
    <script src="../assets/js/open_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/add_entry_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/view_engagement_details.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/number_of_weeks.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/search.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/client_dropdown.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/dynamic_add_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/dynamic_manage_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/manage_entry_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/show_entries.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/edit_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/delete_entry.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/view_entry_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/view_user_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/filter_employees.js?v=<?php echo time(); ?>"></script>
    
    <script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>

    <style>
      /* light highlight for time off cells */
      .timeoff-cell { background-color: rgb(217,217,217) !important; }
      <?php if ($isAdmin): ?>
      .timeoff-cell:hover { background-color: #e0f7fa !important; }
      <?php endif; ?>
      .timeoff-corner { position: absolute; top: 2px; right: 6px; font-size: .50rem; }
      .timeoff-card { 
    border: 2px dashed rgb(209,226, 159) !important;
    background: rgb(246, 249, 236) !important;
}

/* .table-responsive {
    overflow-x: auto !important;
    white-space: nowrap !important;
} */

    .tbl-container {
        max-width: fit-content !important;
        max-height: fit-content !important;
    }

    .tbl-fixed {
        overflow-x: scroll !important;
        overflow-y: scroll !important;
        height: fit-content !important;
        max-height: 70vh !important;
        margin-top: 40px !important;
    }
    

    table {
        min-width: max-content !important;
        /* border-collapse: separate !important; */
    }

    .week-col {
    min-width: 140px;
    max-width: 140px;
}


    table th {
        position: sticky !important;
        top: 0px !important;
        z-index: 2 !important;
    }
    /* Sticky first column cells */
/* Sticky first column */
table th:first-child,
table td:first-child {
    position: sticky;
    left: 0;
    z-index: 2;
    background: white;
}

/* Sticky header row */
table thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: white;
}

/* Top-left cell (corner) above everything */
table thead th:first-child {
    z-index: 3;
}

/* Scroll container limits visible weeks */
#scheduleContainer {
    overflow-x: auto;
    white-space: nowrap;
    max-width: calc(140px * 8); /* 7 weeks + employee column */
}



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
                    <a href="?week_offset=<?php //echo $weekOffset - 1; ?>" 
                       class="btn btn-outline-secondary btn-sm me-2" style="border-color: rgb(229,229,229);"><i class="bi bi-chevron-left"></i></a>
        
                    <span class="fw-semibold"><?php //echo $rangeLabel; ?></span>
        
                    <a href="?week_offset=<?php //echo $weekOffset + 1; ?>" 
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

<div class="tbl-container">


        <div id="scheduleContainer" class="table-responsive tbl-fixed" style="overflow-x:auto; white-space: nowrap;">
            <table class="table table-bordered align-middle text-center" style="min-width: max-content;">
                <thead class="table-light">
                    <tr>
                        <th class="text-start align-middle"><i class="bi bi-people me-2"></i>Employee</th>

                        <?php foreach ($mondays as $idx => $monday): ?>
                            <?php 
                            $weekStart = $monday;
                            $isCurrent = ($idx === $currentWeekIndex);
                            ?>
                            <th class="align-middle <?php echo $isCurrent ? 'highlight-today' : ''; ?>">
                                <?php echo date('M j', $weekStart); ?><br>
                                <small class="text-muted">Week of <?php echo date('n/j', $weekStart); ?></small>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody id="employeesTableBody">
                <?php foreach ($employees as $userId => $employee): ?>
                    <?php
                    $fullName = htmlspecialchars($employee['full_name']);
                    $nameParts = explode(' ', trim($fullName));
                    $initials = '';
                    foreach ($nameParts as $part) {
                        $initials .= strtoupper(substr($part, 0, 1));
                    }
                    $role = htmlspecialchars($employee['role']);
                    ?>
                    <tr>
                        <td class="text-start employee-name">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center me-3"
                                     style="width: 40px; height: 40px; font-size: 14px; font-weight: 500;">
                                  <?php echo $initials; ?>
                                </div>
                                <div>
                                  <div class="fw-semibold"><?php echo $fullName; ?></div>
                                  <div class="text-muted text-capitalize" style="font-size: 12px;"><?php echo $role; ?></div>
                                </div>
                            </div>
                        </td>

                        <?php foreach ($mondays as $idx => $monday): ?>
                            <?php 
                            $weekStart = $monday;
                            $isCurrent = ($idx === $currentWeekIndex);

                            $weekKey = date('Y-m-d', $weekStart);
                            $entriesForWeek = $entries[$userId][$weekKey] ?? [];
                            $cellContent = "";

                            // Build cell content and capture time off
                            $hasTimeOff = false;
                            $timeOffHours = 0;

                            if (!empty($entriesForWeek)) {
                                // First pass: collect time off hours
                                foreach ($entriesForWeek as $entry) {
                                    if (!empty($entry['is_timeoff']) && intval($entry['is_timeoff']) === 1) {
                                        $hasTimeOff = true;
                                        $timeOffHours += floatval($entry['assigned_hours']);
                                    }
                                }

                                // Second pass: render ONLY regular entries as badges
                                foreach ($entriesForWeek as $entry) {
                                    if (empty($entry['is_timeoff']) || intval($entry['is_timeoff']) !== 1) {
                                        $engagementStatus = strtolower($entry['engagement_status'] ?? 'confirmed');
                                        switch ($engagementStatus) {
                                            case 'confirmed': $entry_class = 'badge-confirmed'; break;
                                            case 'pending': $entry_class = 'badge-pending'; break;
                                            case 'not_confirmed': $entry_class = 'badge-not-confirmed'; break;
                                            default: $entry_class = 'badge-confirmed'; break;
                                        }
                                        $clientName = htmlspecialchars($entry['client_name']);
                                        $assignedHours = htmlspecialchars($entry['assigned_hours']);
                                        $cellContent .= "<span class='badge badge-status $entry_class mt-1'>{$clientName} ({$assignedHours})</span><br>";
                                    }
                                }
                            } else {
                                $cellContent = "<span class='text-muted'>" . ($isAdmin ? "+" : "") . "</span>";
                            }

                            // Build td class list
                            $tdClass = ($isCurrent ? '' : '');
                            if ($hasTimeOff) {
                                $tdClass .= 'position-relative timeoff-cell ';
                            }
                            ?>

                            <?php if ($isAdmin): ?>
                                <td class="addable <?php echo $tdClass; ?>" 
                                    style="cursor:pointer;"
                                    data-user-id="<?php echo $userId; ?>" 
                                    data-user-name="<?php echo htmlspecialchars($fullName); ?>"
                                    data-week-start="<?php echo $weekKey; ?>">

                                    <?php 
                                      if ($hasTimeOff) {
                                          echo "<span class='timeoff-corner text-danger fw-semibold'>{$timeOffHours}</span>";
                                      }
                                      echo $cellContent; 
                                    ?>
                                </td>
                            <?php else: ?>
                                <td class="<?php echo $tdClass; ?>">
                                    <?php 
                                      if ($hasTimeOff) {
                                          echo "<span class='timeoff-corner text-danger'>{$timeOffHours}</span>";
                                      }
                                      echo $cellContent; 
                                    ?>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div> <!-- tbl-container -->

    <!-- end master schedule table -->

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
    

    <script>
const scheduleContainer = document.getElementById('scheduleContainer');
let weekOffset = <?php echo $weekOffset; ?>;

scheduleContainer.addEventListener('scroll', () => {
    // If scrolled near the right end, load next week
    if (scheduleContainer.scrollLeft + scheduleContainer.clientWidth >= scheduleContainer.scrollWidth - 50) {
        loadMoreWeeks(weekOffset + 1); // load next batch of weeks
        weekOffset++;
    }

    // Optional: handle scrollLeft near 0 to load previous weeks
});

function loadMoreWeeks(offset) {
    fetch(`load_weeks.php?week_offset=${offset}`)
        .then(res => res.text())
        .then(html => {
            // Append new columns to the table
            const table = scheduleContainer.querySelector('table');
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;

            // Append thead columns
            const newThs = tempDiv.querySelectorAll('thead th');
            const theadRow = table.querySelector('thead tr');
            newThs.forEach(th => theadRow.appendChild(th));

            // Append tbody cells for each row
            const newRows = tempDiv.querySelectorAll('tbody tr');
            const tbodyRows = table.querySelectorAll('tbody tr');
            newRows.forEach((newRow, i) => {
                newRow.querySelectorAll('td').forEach(td => tbodyRows[i].appendChild(td));
            });
        });
}
</script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div>
</body>
</html>
