<?php
require_once '../includes/db.php';
session_start();

$isAdmin = isset($_SESSION['role']) && strtolower($_SESSION['user_role']) === 'admin';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// getting week range, current monday, and start monday
  // $today is midnight timestamp for today
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

// Example: fetch clients with assigned hours and status

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

      // // open ManageorAddModal
      //   const assignments = <?php echo json_encode($assignments); ?>;

      //   document.addEventListener('DOMContentLoaded', () => {
      //   document.querySelectorAll('td.addable').forEach(td => {
      //     td.addEventListener('click', function () {
      //       const userId = this.dataset.userId;
      //       const weekStart = this.dataset.weekStart;
      //       const employeeName = this.dataset.employeeName || 'Employee'; // Optional

      //       console.log('Clicked cell userId:', userId);
      //       console.log('Clicked cell weekStart:', weekStart);

      //       // This function now decides which modal to open
      //       openManageOrAddModal(userId, employeeName, weekStart);
      //     });
      //   });
      //   });

      //     function openManageOrAddModal(user_id, employeeName, weekStart) {
      //   console.log("Modal triggered:", user_id, employeeName, weekStart);

      //   const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] 
      //     ? assignments[user_id][weekStart] 
      //     : [];
      //   console.log("Assignments for week:", assignmentsForWeek);

      //   const hasAssignments = Array.isArray(assignmentsForWeek) && assignmentsForWeek.length > 0;

      //   if (hasAssignments) {
      //     // Show Manage/Add modal
      //     const manageAddModal = new bootstrap.Modal(document.getElementById('manageAddModal'));
      //     manageAddModal.show();

      //     document.getElementById('manageAssignmentsButton').onclick = function() {
      //       openManageAssignmentsModal(user_id, employeeName, weekStart);
      //     };
      //     document.getElementById('addAssignmentsButton').onclick = function() {
      //       openAddAssignmentModal(user_id, employeeName, weekStart);
      //     };
      //   } else {
      //     // Directly open Add Assignment modal
      //     openAddAssignmentModal(user_id, employeeName, weekStart);
      //   }
      //   }
      // // end ManageOrAddModal

      // // open addAssignmentModal
      //     function openAddAssignmentModal(user_id, employeeName, weekStart) {
      //       if (!weekStart || isNaN(new Date(weekStart).getTime())) {
      //         console.warn('Invalid weekStart date:', weekStart);
      //         return;
      //       }

      //       document.getElementById('modalUserId').value = user_id;
      //       document.getElementById('modalWeek').value = weekStart;  // must be "YYYY-MM-DD"
      //       document.getElementById('modalEmployeeNameDisplay').textContent = employeeName;

      //       const options = { year: 'numeric', month: 'short', day: 'numeric' };
      //       const weekDate = new Date(weekStart);
      //       document.getElementById('modalWeekDisplay').textContent = weekDate.toLocaleDateString(undefined, options);

      //       // Reset UI states
      //       document.getElementById('entryTypePrompt').classList.remove('d-none');
      //       document.getElementById('timeOffEntryContent').classList.add('d-none');
      //       document.getElementById('newAssignmentContent').classList.add('d-none');

      //       // Clear inputs
      //       document.getElementById('selectedClient').textContent = 'Select a client';
      //       document.getElementById('engagementInput').value = '';
      //       document.getElementById('assignedHours').value = '';
      //       document.getElementById('timeOffHours').value = '';

      //       // Reset dropdown aria
      //       const dropdownBtn = document.getElementById('dropdownBtn');
      //       if (dropdownBtn) {
      //         dropdownBtn.setAttribute('aria-expanded', 'false');
      //       }

      //       // Show modal
      //       const assignmentModal = new bootstrap.Modal(document.getElementById('assignmentModal'));
      //       assignmentModal.show();
      //     }
      // // end open addAssignmentModal

      // // open manageAssignementModal
      //     function openManageAssignmentsModal(user_id, employeeName, weekStart) {
      //         const formattedDate = new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      //         document.getElementById('assignmentsModalTitle').innerText = `Manage Assignments for Week of ${formattedDate}`;
      //         document.getElementById('assignmentsModalSubheading').innerText = `Consultant: ${employeeName}`;

      //         // Fetch assignments for the user and week
      //         const assignments = <?php echo json_encode($assignments); ?>;
      //         const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] ? assignments[user_id][weekStart] : [];
      //         showAssignments(assignmentsForWeek);

      //         const assignmentsModal = new bootstrap.Modal(document.getElementById('assignmentsModal'));
      //         assignmentsModal.show();
      //     }
      // // end manageAssignementModal

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

<!-- Modal for Manage or Add Assignment -->
  <div class="modal fade" id="manageAddModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manage or Add Assignment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

  <!-- Initial buttons -->
  <div id="manageAddButtons">
    <button id="manageAssignmentsButton" class="btn btn-warning w-100 mb-2">Manage Existing Assignments</button>
    <button id="addAssignmentsButton" class="btn btn-success w-100">Add New Assignment</button>
  </div>

  <div id="assignmentsListing" class="d-none">
  <button id="backToButtons" class="btn btn-secondary mb-3">Back</button>
  <div id="assignmentsListContainer"><!-- Cards will render here --></div>
  </div>

  </div>
    </div>
  </div>
  </div>

<!-- end manage or add assignment -->

<!-- Modal for Managing Assignments -->
  <div class="modal fade" id="assignmentsModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title" id="assignmentsModalTitle">Manage Assignments</h5>
            <small id="assignmentsModalSubheading" class="text-muted"></small>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div id="existingAssignments"></div>
        </div>
      </div>
    </div>
  </div>
<!-- end mangaging assignments -->

<!-- Modal for Editing Assignment -->
  <div class="modal fade" id="editAssignmentModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Assignment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="editAssignmentForm" action="update_entry.php" method="POST">
            <input type="hidden" id="editAssignmentId" name="assignment_id">
            <div class="mb-3">
              <label for="editAssignedHours" class="form-label">Assigned Hours</label>
              <input type="number" class="form-control" id="editAssignedHours" name="assigned_hours" min="0" required>
            </div>
            <div class="mb-3 text-end">
              <button type="submit" id="editSubmitBtn" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
<!-- end editing assignment -->

<!-- WORKING Modal for Adding assignment -->
  <div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="assignmentForm" action="add_assignment.php" method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="assignmentModalLabel">
            <i class="bi bi-calendar-range me-2"></i>New Entry<br>
              <span class="text-muted" style="font-size: 12px; font-weight: 400; padding-top: 0;">
                Assign work for <strong><span id="modalEmployeeNameDisplay"></span></strong> during week of <strong><span id="modalWeekDisplay"></span></strong>
              </span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <!-- Hidden inputs -->
            <input type="hidden" id="modalUserId" name="user_id" value="">
            <input type="hidden" id="modalWeek" name="week_start" value="">
          

          <!-- Initial prompt with two buttons -->
          <div id="entryTypePrompt" class="text-center">
            <p>Please choose the type of entry:</p>
            <button type="button" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18);" id="btnTimeOffEntry">Time Off Entry</button>
            <button type="button" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18);" id="btnNewAssignment">New Assignment</button>
          </div>

          <!-- Time Off Entry content: only hours input -->
          <div id="timeOffEntryContent" class="d-none">
            <div class="mb-3">
              <label for="timeOffHours" class="form-label">Hours</label>
              <input type="number" class="form-control" id="timeOffHours" name="time_off_hours" min="0" step="0.25" required>
            </div>
          </div>

          <!-- New Assignment content: client dropdown + hours input -->
          <div id="newAssignmentContent" class="d-none">

          
            <!-- Custom Client Dropdown -->
            <div class="mb-3 custom-dropdown">
              <label for="engagementInput" class="form-label">Client Name</label>
              <div
                class="dropdown-btn"
                id="dropdownBtn"
                tabindex="0"
                aria-haspopup="listbox"
                aria-expanded="false"
                role="combobox"
                aria-labelledby="selectedClient"
              >
                <span id="selectedClient" class="text-muted">Select a client</span>
                <span>&#9662;</span> <!-- Down arrow -->
              </div>

              <div
                class="dropdown-list"
                id="dropdownList"
                aria-expanded="true"
                role="listbox"
                tabindex="-1"
                aria-labelledby="selectedClient"
                style="display: block !important;"
              >
                <?php 
                  $statusDisplayMap = [
                    'confirmed' => 'Confirmed',
                    'pending' => 'Pending',
                    'not_confirmed' => 'Not Confirmed'
                  ];
                  $statusClassMap = [
                    'confirmed' => 'text-confirmed',
                    'pending' => 'text-pending',
                    'not_confirmed' => 'text-not-confirmed'
                  ];
                ?>
                <?php foreach ($clientsWithHours as $client): ?>
                  <?php
                    $statusKey = strtolower($client['status']);
                    $statusText = $statusDisplayMap[$statusKey] ?? ucfirst($statusKey);
                    $statusClass = $statusClassMap[$statusKey] ?? 'badge-default';
                  ?>
                  <div
                    class="dropdown-item"
                    data-engagement-id="<?php echo htmlspecialchars($client['engagement_id']); ?>"
                    data-client-name="<?php echo htmlspecialchars($client['client_name']); ?>"
                    role="option"
                    tabindex="0"
                  >
                    <div>
                      <span class="fw-semibold"><?php echo htmlspecialchars($client['client_name']); ?></span><br>
                      <small class="text-muted">
                        <span class="text-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                        <i class="bi bi-dot"></i> 
                        <?php echo number_format($client['assigned_hours'], 2); ?> / <?php echo number_format($client['total_available_hours'], 2); ?> hrs
                      </small>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              
              <!-- Hidden input to hold selected value for form submission -->
              <input type="hidden" id="engagementInput" name="engagement_id">
            </div>
              
            <!-- Assigned hours -->
            <div class="mb-3">
              <label for="assignedHours" class="form-label">Hours</label>
              <input type="number" class="form-control" id="assignedHours" name="assigned_hours" min="0" step="0.25" required>
            </div>
          </div>
        </div>

        <div id="modal-footer" class="modal-footer d-none">
          <button type="button" class="btn badge text-black p-2 text-decoration-none fw-medium" style="font-size: .875rem; box-shadow: inset 0 0 0 1px rgb(229,229,229);" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18); border:none !important;">Submit</button>
        </div>
      </form>
    </div>
  </div>
  </div>
<!-- end Adding assignment -->

<!-- Modal for Adding Engagement -->
  <div class="modal fade" id="engagementModal" tabindex="-1" aria-labelledby="engagementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="engagementForm" action="add_engagement.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="engagementModalLabel">Add Engagement</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">

            <div class="mb-3">
              <label for="client_name" class="form-label">Client Name</label>
              <input type="text" class="form-control" id="client_name" name="client_name" required>
            </div>

            <div class="mb-3">
              <label for="total_available_hours" class="form-label">Total Available Hours</label>
              <input type="number" step="0.1" min="0" class="form-control" id="total_available_hours" name="total_available_hours" required>
            </div>

            <div class="mb-3">
              <label for="status" class="form-label">Status</label>
              <select id="status" name="status" class="form-select" required>
                <option value="" disabled selected>Select status</option>
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
                <option value="not_confirmed">Not Confirmed</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="notes" class="form-label">Notes</label>
              <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Add any notes here..."></textarea>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Engagement</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<!-- end adding engagement -->

<?php endif; ?>

<!-- Modal for Engagement Details -->
  <div class="modal fade" id="clientDetailsModal" tabindex="-1" aria-labelledby="clientDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="clientDetailsModalLabel">Engagement Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
                  
        <div class="modal-body">
          <!-- Engagement Name -->
          <h3 id="clientName" class="text-center mb-3 fw-bold"></h3>

          <!-- Hidden ID for use in AJAX -->
          <input type="text" id="engagementId" value="">

          <!-- Engagement Status Editor (Inline Editable) -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Engagement Status</label>
            <div id="engagement-status-container">
              <span id="engagement-status-display" class="badge bg-warning text-dark" style="cursor: pointer;">Pending</span>
              <select id="engagement-status-select" class="form-select w-auto d-inline-block mt-2 d-none">
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
                <option value="not_confirmed">Not Confirmed</option>
              </select>
            </div>
          </div>

          <!-- Utilization Progress Bar -->
          <div class="mb-4">
            <h6>Total Assigned Hours</h6>
            <div class="d-flex justify-content-between">
              <span id="totalAssignedHours" class="fw-bold fs-5 text-dark"></span>
              <span id="totalHours" class="text-muted">/ <span id="totalAvailableHours">1000</span> hrs</span>
            </div>
            <div class="progress mt-2" style="height: 20px; border-radius: 10px;">
              <div id="utilizationBar" class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: 0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="1000"></div>
            </div>
          </div>

          <!-- Assigned Employees Section -->
          <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0">Assigned Consultants</h6>
            </div>
            <div class="card-body">
              <div id="assignedEmployees" class="list-group"></div>
            </div>
          </div>

          <!-- Notes Section -->
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0">Client Notes</h6>
            </div>
            <div class="card-body">
              <p id="clientNotes" class="text-muted">No notes available.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
<!-- end engagement details -->

<!-- Modal for Employee Details -->
  <div class="modal fade" id="employeeDetailsModal" tabindex="-1" aria-labelledby="employeeDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="employeeDetailsModalLabel">Employee Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <!-- Employee Role -->
          <h4 id="employeeName" class="text-center mb-3 fw-bold"></h4>
          <p id="employeeRole" class="text-muted mb-3"></p>

          <!-- Assigned Hours and Assignments -->
          <div class="mb-4">
            <h6>Total Assigned Hours:</h6>
            <div class="d-flex justify-content-between">
              <span id="totalAssignedHoursEmployee" class="fw-bold fs-5 text-dark"></span>
              <span id="totalAvailableHoursEmployee" class="text-muted">/ <span id="totalAvailableHoursEmployeeVal">1000</span> hrs</span>
            </div>
            <div class="progress mt-2" style="height: 20px; border-radius: 10px;">
              <div id="utilizationBarEmployee" class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: 0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="1000"></div>
            </div>
          </div>

          <!-- Assigned Assignments Section -->
          <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0">Upcoming Assignments</h6>
            </div>
            <div class="card-body">
              <div id="assignedAssignments" class="list-group"></div>
            </div>
          </div>

          <!-- Notes Section (Optional) -->
          <!-- <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0">Employee Notes</h6>
            </div>
            <div class="card-body">
              <p id="employeeNotes" class="text-muted">No notes available.</p>
            </div>
          </div> -->
        </div>
      </div>
    </div>
  </div>
<!-- end employee details modal -->


<script src="../assets/js/view_engagement_details.js"></script>
<script src="../assets/js/number_of_weeks.js"></script>
<script src="../assets/js/search.js"></script>
<script src="../assets/js/client_dropdown.js"></script>
<script src="../assets/js/dynamic_add_modal.js"></script>
<script src="../assets/js/dynamic_manage_modal.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>