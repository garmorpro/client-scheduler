<?php
require_once '../includes/db.php';
session_start();

$isAdmin = isset($_SESSION['role']) && strtolower($_SESSION['user_role']) === 'admin';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

  $today = strtotime('today');

  // Calculate Mondays as timestamps (you already do this correctly)
  $currentMonday = strtotime('monday this week', $today);
  $weekOffset = isset($_GET['week_offset']) ? intval($_GET['week_offset']) : 0;
  $startMonday = strtotime("-2 weeks", $currentMonday);
  $startMonday = strtotime("+{$weekOffset} weeks", $startMonday);

  $mondays = [];
  for ($i = 0; $i < 7; $i++) {
      $mondays[] = strtotime("+{$i} weeks", $startMonday);
  }

  // Range label for header
  $firstWeek = reset($mondays);
  $lastWeek = end($mondays);
  $rangeLabel = "Week of " . date('n/j', $firstWeek) . " - Week of " . date('n/j', $lastWeek);
// end getting week range, current monday, and start monday

// Get employees from users table
$employees = [];
$userQuery = "SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name, role FROM users WHERE status = 'active' AND role IN ('staff', 'senior')";
$userResult = $conn->query($userQuery);
if ($userResult) {
    while ($userRow = $userResult->fetch_assoc()) {
        $employees[$userRow['user_id']] = [
            'full_name' => $userRow['full_name'],
            'role' => $userRow['role']
        ];
    }
}

// Query to fetch active clients from the engagements table
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

$query = "
    SELECT 
        a.assignment_id,
        a.user_id,
        a.engagement_id,
        e.client_name,
        a.week_start,
        a.assigned_hours,
        e.status AS engagement_status
    FROM 
        assignments a
    JOIN 
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

$assignments = [];
while ($row = $result->fetch_assoc()) {
    $assignments[$row['user_id']][$row['week_start']][] = [
        'assignment_id' => $row['assignment_id'],
        'client_name' => $row['client_name'],
        'assigned_hours' => $row['assigned_hours'],
        'engagement_id' => $row['engagement_id'],
        'engagement_status' => $row['engagement_status'],
    ];
}

$stmt->close();

$dropdownquery = "
  SELECT 
    e.engagement_id,
    e.client_name,
    e.status,
    e.total_available_hours,
    IFNULL(SUM(a.assigned_hours), 0) AS assigned_hours
  FROM engagements e
  LEFT JOIN assignments a ON a.engagement_id = e.engagement_id
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

    <style>
      body {
        font-size: 14px !important;
      }
        .form-select, .form-control { background-color: #f9fafb; border-radius: 8px; }
        /* .highlight-today { background-color: lightblue !important; } */
        td.addable:hover { background-color: #e0f7fa; cursor: pointer; }
    </style>

    <script>
      function autoSubmitDateFilter() {
        document.getElementById("filterForm").submit();
      }
      const assignments = <?php echo json_encode($assignments); ?>;
    </script>

    <script src="../assets/js/add_entry_modal.js"></script>
    <script src="../assets/js/manage_entry_modal.js"></script>
    <script src="../assets/js/edit_modal.js"></script>
    <script src="../assets/js/delete_entry.js"></script>
    <script src="../assets/js/view_assignment_modal.js"></script>
    <script src="../assets/js/view_user_modal.js"></script>


</head>
<body class="d-flex">
<?php include_once '../templates/sidebar.php'; ?>
<div class="flex-grow-1 p-4">
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

            <!-- Search Bar -->
            <div class="col-md-6">
                <input type="text" 
                       id="searchInput" 
                       class="form-control" 
                       placeholder="Search projects, clients, or employees..." 
                       onkeyup="searchQuery()" />
                <div id="searchResults" 
                     class="dropdown-menu" 
                     style="max-height: 200px; overflow-y: auto; display:none;"></div>
            </div>

            <!-- Week Selector -->
            <div class="col-md-6 d-flex justify-content-end align-items-center">
                <a href="?week_offset=<?php echo $weekOffset - 1; ?>" 
                   class="btn btn-outline-secondary btn-sm me-2" style="border-color: rgb(229,229,229);"><i class="bi bi-chevron-left"></i></a>

                <span class="fw-semibold"><?php echo $rangeLabel; ?></span>

                <a href="?week_offset=<?php echo $weekOffset + 1; ?>" 
                   class="btn btn-outline-secondary btn-sm ms-2" style="border-color: rgb(229,229,229);"><i class="bi bi-chevron-right"></i></a>
            </div>

        </form>
    </div>
<!-- end upper search and week range selector -->

<!-- Master Schedule table -->
  <?php
  // Make sure these are set and valid:
  // $today = strtotime('today');
  // $mondays = array of Monday timestamps (7 weeks shown)
  // $employees = array of employees keyed by userId, each with 'full_name' and 'role'
  // $assignments = nested array as explained above
  // $isAdmin = bool if current user is admin

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

  <div class="table-responsive">
      <table class="table table-bordered align-middle text-center">
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

          <tbody>
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
            <td class="text-start">
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
                $assignmentsForWeek = $assignments[$userId][$weekKey] ?? [];
                $cellContent = "";

                if (!empty($assignmentsForWeek)) {
                    foreach ($assignmentsForWeek as $assignment) {
                        $engagementStatus = strtolower($assignment['engagement_status'] ?? 'confirmed');
                        switch ($engagementStatus) {
                            case 'confirmed': $badgeColor = 'success'; break;
                            case 'pending': $badgeColor = 'purple'; break;
                            case 'not_confirmed': $badgeColor = 'primary'; break;
                            default: $badgeColor = 'secondary'; break;
                        }
                        $clientName = htmlspecialchars($assignment['client_name']);
                        $assignedHours = htmlspecialchars($assignment['assigned_hours']);
                        $cellContent .= "<span class='badge bg-$badgeColor'>{$clientName} ({$assignedHours})</span><br>";
                    }
                } else {
                    $cellContent = "<span class='text-muted'>+</span>";
                }

                $tdClass = $isCurrent ? 'highlight-today' : '';
                ?>

                <?php if ($isAdmin): ?>
                    <?php if (!empty($assignmentsForWeek)): ?>
                        <!-- Has assignments → open ManageAssignments modal -->
                        <td class="addable <?php echo $tdClass; ?>" style="cursor:pointer;"
                            data-user-id="<?php echo $userId; ?>" 
                            data-week-start="<?php echo $weekKey; ?>"
                            onclick='
                                event.stopPropagation();
                                console.log("Assignments empty?", false);
                                openManageAssignmentsModal(
                                    "<?php echo $userId; ?>",
                                    <?php echo json_encode($fullName); ?>,
                                    "<?php echo $weekKey; ?>"
                                )
                            '>
                            <?php echo $cellContent; ?>
                        </td>
                    <?php else: ?>
                        <!-- No assignments → open AddAssignment modal -->
                        <td class="addable <?php echo $tdClass; ?>" style="cursor:pointer;"
                            data-user-id="<?php echo $userId; ?>" 
                            data-week-start="<?php echo $weekKey; ?>"
                            onclick='
                                event.stopPropagation();
                                console.log("Assignments empty?", true);
                                openAddAssignmentModal(
                                    "<?php echo $userId; ?>",
                                    <?php echo json_encode($fullName); ?>,
                                    "<?php echo $weekKey; ?>"
                                )
                            '>
                            <?php echo $cellContent; ?>
                        </td>
                    <?php endif; ?>
                <?php else: ?>
                    <td class="<?php echo $tdClass; ?>">
                        <?php echo $cellContent; ?>
                    </td>
                <?php endif; ?>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
 </tbody>


      </table>
  </div>

<!-- end master schedule table -->



<?php if ($isAdmin): ?>

<?php include_once '../includes/modals/manage_entries_prompt.php'; ?>
<?php include_once '../includes/modals/manage_assignments.php'; ?>
<?php include_once '../includes/modals/edit_assignment.php'; ?>
<?php include_once '../includes/modals/add_assignment.php'; ?>
<?php include_once '../includes/modals/add_engagement.php'; ?>

<?php endif; ?>

<?php include_once '../includes/modals/engagement_details.php'; ?>
<?php include_once '../includes/modals/user_details.php'; ?>


<script src="../assets/js/view_engagement_details.js"></script>
<script src="../assets/js/number_of_weeks.js"></script>
<script src="../assets/js/search.js"></script>
<script src="../assets/js/client_dropdown.js"></script>
<script src="../assets/js/dynamic_add_modal.js"></script>
<script src="../assets/js/dynamic_manage_modal.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>