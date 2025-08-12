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

    // open modal for Manage Assignments or Add Engagement
    function openManageOrAddModal(user_id, employeeName, weekStart) {
        console.log("Modal triggered:", user_id, employeeName, weekStart);
        // Fetch assignments for the user and week
        const assignments = <?php echo json_encode($assignments); ?>;
        const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] ? assignments[user_id][weekStart] : [];

        if (assignmentsForWeek.length > 0) {
            // Show "Manage" or "Add" modal for existing assignments
            const manageAddModal = new bootstrap.Modal(document.getElementById('manageAddModal'));
            manageAddModal.show();

            document.getElementById('manageAssignmentsButton').onclick = function() {
                // Manage Assignments: Open the Manage Assignments modal
                openManageAssignmentsModal(user_id, employeeName, weekStart);
            };
            document.getElementById('addAssignmentsButton').onclick = function() {
                // Add Assignment: Open the Add Engagement modal
                openAddassignmentModal(user_id, employeeName, weekStart);
            };
        } else {
            // If no existing assignments, directly show Add Engagement modal
            openAddassignmentModal(user_id, employeeName, weekStart);
        }
    }

    function openManageAssignmentsModal(user_id, employeeName, weekStart) {
        const formattedDate = new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        document.getElementById('assignmentsModalTitle').innerText = `Manage Assignments for Week of ${formattedDate}`;
        document.getElementById('assignmentsModalSubheading').innerText = `Consultant: ${employeeName}`;

        // Fetch assignments for the user and week
        const assignments = <?php echo json_encode($assignments); ?>;
        const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] ? assignments[user_id][weekStart] : [];
        showAssignments(assignmentsForWeek);

        const assignmentsModal = new bootstrap.Modal(document.getElementById('assignmentsModal'));
        assignmentsModal.show();
    }



// open addAssignmentModal
    function openAddassignmentModal(user_id, employeeName, weekStart) {
    document.getElementById('modalUserId').value = user_id;
    document.getElementById('modalWeek').value = weekStart;  // must be "YYYY-MM-DD"
    document.getElementById('modalEmployeeNameDisplay').textContent = employeeName;

    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    const weekDate = new Date(weekStart);
    document.getElementById('modalWeekDisplay').textContent = weekDate.toLocaleDateString(undefined, options);

    // Reset custom dropdown selection and hidden input
    document.getElementById('selectedClient').textContent = 'Select a client';
    document.getElementById('engagementInput').value = '';

    // Reset assigned hours input
    document.getElementById('assignedHours').value = '';

    const assignmentModal = new bootstrap.Modal(document.getElementById('assignmentModal'));
    assignmentModal.show();
  }

// end open addAssignmentModal


// Update openEditModal to handle dynamic elements properly
function openEditModal(event) {
    const buttonElement = event.target;
    const assignmentId = buttonElement.getAttribute('data-assignment-id');
    const assignedHours = buttonElement.getAttribute('data-assigned-hours');

    // Set the assignment ID and assigned hours in the modal
    document.getElementById('editAssignmentId').value = assignmentId;
    document.getElementById('editAssignedHours').value = assignedHours;

    // Open the modal
    const editModal = new bootstrap.Modal(document.getElementById('editAssignmentModal'));
    editModal.show();
}



// Delete an assignment
function deleteAssignment(assignmentId) {
    if (confirm('Are you sure you want to delete this assignment?')) {
        fetch('delete-assignment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `assignment_id=${assignmentId}`
        })
        .then(response => response.text())
        .then(result => {
            if (result === 'success') {
                location.reload(); // Reload the page to reflect changes
            } else {
                alert('Failed to delete assignment.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the assignment.');
        });
    }
}



function openassignmentModal(engagementId) {
    // Set the engagementId in the hidden input field before fetching the data
    document.getElementById('engagementId').value = engagementId;

    // Fetch the engagement details using the engagement ID
    fetch(`engagement-details.php?id=${engagementId}`)
        .then(response => response.json())
        .then(data => {
            // Set engagement (client) name
            document.getElementById('clientName').innerText = data.client_name;

            // Set total assigned hours
            let totalAssignedHours = parseFloat(data.total_hours) || 0;
            let totalAvailableHours = parseFloat(data.max_hours) || 0;

            // Set total assigned hours text
            document.getElementById('totalAssignedHours').innerText = totalAssignedHours;
            document.getElementById('totalHours').innerText = `/ ${totalAvailableHours} hrs`;

            let utilizationPercent = totalAvailableHours > 0
                ? (totalAssignedHours / totalAvailableHours) * 100
                : 0;

            const bar = document.getElementById('utilizationBar');

            // Set bar width and ARIA attributes
            bar.style.width = utilizationPercent + "%";
            bar.setAttribute('aria-valuenow', totalAssignedHours);
            bar.setAttribute('aria-valuemax', totalAvailableHours);

            // Remove any existing color classes
            bar.classList.remove('bg-success', 'bg-danger');

            // Add the appropriate color
            if (totalAssignedHours > totalAvailableHours) {
                bar.classList.add('bg-danger');
            } else {
                bar.classList.add('bg-success');
            }

            // Set assigned employees
            let assignedEmployees = data.assigned_employees;
            document.getElementById('assignedEmployees').innerHTML = assignedEmployees;

            // Set client notes
            const notes = data.notes?.trim();
            document.getElementById('clientNotes').innerText = notes ? notes : "No notes available.";

            // Show the modal after the engagement details are set
            const assignmentModal = new bootstrap.Modal(document.getElementById('clientDetailsModal'));
            assignmentModal.show();
        })
        .catch(error => console.error('Error fetching engagement details:', error));
}



function openEmployeeModal(employeeId) {
    fetch(`employee-details.php?id=${employeeId}`)
        .then(response => {
            if (!response.ok) throw new Error("Network error");
            return response.json();
        })
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            document.getElementById('employeeName').innerText = data.full_name;
            document.getElementById('employeeRole').innerText = data.role;

            document.getElementById('totalAssignedHoursEmployee').innerText = data.total_assigned_hours;
            document.getElementById('totalAvailableHoursEmployeeVal').innerText = data.total_available_hours;

            // Set progress bar
            const percent = (data.total_assigned_hours / data.total_available_hours) * 100;
            const bar = document.getElementById('utilizationBarEmployee');
            bar.style.width = percent + "%";
            bar.setAttribute('aria-valuenow', data.total_assigned_hours);
            bar.setAttribute('aria-valuemax', data.total_available_hours);

            // Set assignments
            document.getElementById('assignedAssignments').innerHTML = data.assignment_items;

            // Show modal
            const employeeModal = new bootstrap.Modal(document.getElementById('employeeDetailsModal'));
            employeeModal.show();
        })
        .catch(err => {
            console.error("Error fetching employee details:", err);
            alert("Failed to load employee details.");
        });
}

    </script>
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

                          // Format weekStart as Y-m-d string for key lookup
                          $weekKey = date('Y-m-d', $weekStart);

                          // Get assignments for this user and week, default empty array
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
                              <td class="addable <?php echo $tdClass; ?>" style="cursor:pointer;" onclick='openManageOrAddModal(
                                  "<?php echo $userId; ?>",
                                  <?php echo json_encode($fullName); ?>,
                                  "<?php echo $weekKey; ?>"
                              )'>
                                  <?php echo $cellContent; ?>
                              </td>
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
          <button id="manageAssignmentsButton" class="btn btn-warning w-100 mb-2">Manage Existing Assignments</button>
          <button id="addAssignmentsButton" class="btn btn-success w-100">Add New Assignment</button>
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
          <form id="editAssignmentForm" action="edit-assignment-process.php" method="POST">
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

<!-- Modal for Adding assignment -->
  <div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="assignmentModalLabel">
          <i class="bi bi-calendar-range me-2"></i> New Entry
          <br>
          <span class="text-muted" style="font-size: 12px; font-weight: 400; padding-top: 0;">
            For <strong><span id="modalEmployeeNameDisplay"></span></strong> during week of <strong><span id="modalWeekDisplay"></span></strong>
          </span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Custom Tabs -->
      <div class="custom-tabs" role="tablist">
        <button class="active" data-tab="assignmentTabPane" role="tab" aria-selected="true" aria-controls="assignmentTabPane" tabindex="0">Add Assignment</button>
        <button data-tab="timeoffTabPane" role="tab" aria-selected="false" aria-controls="timeoffTabPane" tabindex="-1">Add Time Off</button>
      </div>

      <!-- Tab Content -->
      <div class="tab-content">
        <!-- Assignment Tab Pane -->
        <div id="assignmentTabPane" class="tab-pane active" role="tabpanel" aria-labelledby="assignmentTab">
          <form id="assignmentForm" action="add_assignment.php" method="POST">
            <!-- Hidden inputs -->
            <input type="hidden" id="modalUserId" name="user_id" value="">
            <input type="hidden" id="modalWeek" name="week_start" value="">

            <!-- Client Dropdown -->
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
                <span>&#9662;</span>
              </div>

              <div
                class="dropdown-list"
                id="dropdownList"
                role="listbox"
                tabindex="-1"
                aria-labelledby="selectedClient"
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
                    $statusClass = $statusClassMap[$statusKey] ?? '';
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

              <input type="hidden" id="engagementInput" name="engagement_id" required>
            </div>

            <!-- Assigned hours -->
            <div class="mb-3">
              <label for="assignedHours" class="form-label">Hours</label>
              <input type="number" class="form-control" id="assignedHours" name="assigned_hours" min="0" step="0.25" required>
            </div>

            <div class="modal-footer p-0 pt-3 border-0">
              <button type="button" class="btn badge text-black p-2 text-decoration-none fw-medium" 
                style="font-size: .875rem; box-shadow: inset 0 0 0 1px rgb(229,229, 229);" 
                data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="badge text-white p-2 text-decoration-none fw-medium" 
                style="font-size: .875rem; background-color: rgb(3,2,18); border:none !important;">Submit</button>
            </div>
          </form>
        </div>

        <!-- Time Off Tab Pane -->
        <div id="timeoffTabPane" class="tab-pane" role="tabpanel" aria-labelledby="timeoffTab">
          <form id="timeoffForm" action="add_timeoff.php" method="POST">
            <input type="hidden" name="user_id" id="timeoffUserId" value="">
            <input type="hidden" name="week_start" id="timeoffWeekStart" value="">

            <div class="mb-3">
              <label for="timeoffReason" class="form-label">Reason for Time Off</label>
              <textarea class="form-control" id="timeoffReason" name="reason" rows="3" required></textarea>
            </div>

            <div class="mb-3">
              <label for="timeoffHours" class="form-label">Hours</label>
              <input type="number" class="form-control" id="timeoffHours" name="hours" min="0" step="0.25" required>
            </div>

            <div class="modal-footer p-0 pt-3 border-0">
              <button type="button" class="btn badge text-black p-2 text-decoration-none fw-medium" 
                style="font-size: .875rem; box-shadow: inset 0 0 0 1px rgb(229,229, 229);" 
                data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="badge text-white p-2 text-decoration-none fw-medium" 
                style="font-size: .875rem; background-color: rgb(3,2,18); border:none !important;">Submit</button>
            </div>
          </form>
        </div>
      </div>

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


<!-- Script: view engagement details -->
  <script>
  document.addEventListener("DOMContentLoaded", function () {
      const statusDisplay = document.getElementById('engagement-status-display');
      const statusSelect = document.getElementById('engagement-status-select');
      const engagementIdInput = document.getElementById('engagementId');
      const modal = document.getElementById('clientDetailsModal');

      // Listen for clicks on buttons with engagement ID (Opening modal)
      const engagementButtons = document.querySelectorAll('.btn[data-engagement-id]');
      engagementButtons.forEach(button => {
          button.addEventListener('click', function () {
              const engagementId = this.getAttribute('data-engagement-id');
              engagementIdInput.value = engagementId; // Set engagement ID in the hidden input field
              console.log('Set Engagement ID:', engagementId);  // Debugging
          });
      });

      // Fetch engagement details when the modal is opened
      modal.addEventListener('shown.bs.modal', function () {
          const engagementId = engagementIdInput.value;

          console.log('Engagement ID on modal open:', engagementId); // Debugging

          if (!engagementId) {
              console.error('Engagement ID is not set.');
              return;
          }

          // Fetch engagement details
          fetch(`get-engagement-details.php?id=${engagementId}`)
      .then(response => response.text()) // Use text() to get the raw response for debugging
      .then(data => {
          console.log('Response:', data); // Check the raw response in the console
          try {
              const jsonData = JSON.parse(data); // Try to parse it manually
              console.log('Parsed JSON:', jsonData);
          } catch (error) {
              console.error('Error parsing JSON:', error);
          }
      })
      .catch(error => {
          console.error('Error fetching engagement details:', error);
          alert("Failed to fetch engagement details.");
      });
      });

      // Handle status change and update via AJAX
      statusSelect.addEventListener('change', function () {
          const newStatus = this.value;
          const engagementId = engagementIdInput.value;

          console.log('Updating Engagement ID:', engagementId, 'New Status:', newStatus); // Debugging

          // Send the status update to the server via AJAX
          fetch('update-engagement-status.php', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                  engagement_id: engagementId,
                  status: newStatus,
              })
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  // Update the status display with the new value
                  statusDisplay.textContent = capitalize(newStatus.replace('-', ' '));
                  statusDisplay.className = `badge ${getStatusClass(newStatus)}`;

                  // Optionally, hide the dropdown and show the status badge again
                  statusSelect.classList.add('d-none');
                  statusDisplay.classList.remove('d-none');
              } else {
                  alert('Failed to update status.');
              }
          })
          .catch(error => {
              console.error('Error updating status:', error);
              alert('Failed to update status.');
          });
      });

      // Helpers
      function capitalize(str) {
          return str.charAt(0).toUpperCase() + str.slice(1);
      }

      function getStatusClass(status) {
          switch (status) {
              case 'confirmed': return 'bg-success';
              case 'pending': return 'bg-warning text-dark';
              case 'not_confirmed': return 'bg-danger';
              default: return 'bg-secondary';
          }
      }
  });

  </script>
<!-- end Script: view engagement details -->


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


<!-- Script: number of weeks -->
  <script>
  function generateWeekInputs() {
      const numberOfWeeks = parseInt(document.getElementById('numberOfWeeks').value);
      const selectedStartDate = document.getElementById('modalWeek').value; // Assume you have a date input with id="startDate"
      const weeksContainer = document.getElementById('weeksContainer');
      weeksContainer.innerHTML = ''; // Clear previous input fields

      if (!selectedStartDate) {
          alert("Please select a start date.");
          return;
      }

      const startDate = new Date(selectedStartDate);

      for (let i = 0; i < numberOfWeeks; i++) {
          const weekDate = new Date(startDate);
          weekDate.setDate(startDate.getDate() + (i * 7)); // Add 7 days per week

          // Format the date to YYYY-MM-DD for input[type="date"]
          const formattedDate = weekDate.toISOString().split('T')[0];

          const weekInput = document.createElement('div');
          weekInput.classList.add('mb-3');
          weekInput.innerHTML = `
              <label for="week_${i+1}" class="form-label">Week ${i + 1}</label>
              <div class="d-flex gap-2 flex-wrap">
                  <input type="date" class="form-control" id="week_${i+1}" name="weeks[]" value="${formattedDate}" required>
                  <input type="number" class="form-control" id="assigned_hours_${i+1}" name="assigned_hours[]" min="0" placeholder="Assigned Hours" required>
                  <select class="form-select" name="statuses[]" required>
                      <option value="confirmed">Confirmed</option>
                      <option value="pending">Pending</option>
                      <option value="not_confirmed">Not Confirmed</option>
                  </select>
              </div>
              <div class='mt-3'></div>
              <hr>
              <div class='mt-3'></div>
          `;
          weeksContainer.appendChild(weekInput);
      }
  }
  </script>
<!-- end script: number of weeks -->

<!-- Script: search -->
  <script>
  function searchQuery() {
      var query = document.getElementById('searchInput').value;
      console.log(query); // Debug log to check the query value
      if (query.length >= 3) {
          fetchSearchResults(query);
      } else {
          document.getElementById('searchResults').style.display = 'none';
      }
  }

  function fetchSearchResults(query) {
      fetch('search.php?query=' + query)
          .then(response => response.json())
          .then(data => {
              let resultsHTML = '';
              data.forEach(result => {
                  resultsHTML += `<a href="#" class="dropdown-item" onclick="openModal(${result.id}, '${result.type}')">${result.name}</a>`;
              });
              if (resultsHTML === '') {
                  resultsHTML = `<a href="#" class="dropdown-item">No results found</a>`;
              }
              document.getElementById('searchResults').innerHTML = resultsHTML;
              document.getElementById('searchResults').style.display = 'block';
          })
          .catch(error => console.error('Error fetching search results:', error));
  }

  function openModal(id, type) {
      if (type === 'employee') {
          openEmployeeModal(id); // Ensure you have this modal function defined
      } else if (type === 'client') {
          openassignmentModal(id); // Ensure you have this modal function defined
      }
  }
  </script>
<!-- end script: search -->

<!-- dropdown menu -->
    <script>
      const dropdownBtn = document.getElementById('dropdownBtn');
    const dropdownList = document.getElementById('dropdownList');
    const selectedClient = document.getElementById('selectedClient');
    const engagementInput = document.getElementById('engagementInput');

    dropdownBtn.addEventListener('click', () => {
      const isOpen = dropdownList.style.display === 'block';
      dropdownList.style.display = isOpen ? 'none' : 'block';
      dropdownBtn.setAttribute('aria-expanded', !isOpen);
    });

    dropdownBtn.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        dropdownList.style.display = 'block';
        dropdownBtn.setAttribute('aria-expanded', 'true');
        dropdownList.querySelector('.dropdown-item').focus();
      }
    });

    dropdownList.querySelectorAll('.dropdown-item').forEach(item => {
      item.addEventListener('click', () => {
        selectClient(item);
      });
      item.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          selectClient(item);
        }
        else if (e.key === 'ArrowDown') {
          e.preventDefault();
          const next = item.nextElementSibling || dropdownList.querySelector('.dropdown-item');
          next.focus();
        }
        else if (e.key === 'ArrowUp') {
          e.preventDefault();
          const prev = item.previousElementSibling || dropdownList.querySelector('.dropdown-item:last-child');
          prev.focus();
        }
        else if (e.key === 'Escape') {
          closeDropdown();
          dropdownBtn.focus();
        }
      });
    });

    document.addEventListener('click', (e) => {
      if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
        closeDropdown();
      }
    });

    function selectClient(item) {
      const clientName = item.getAttribute('data-client-name');
      const engagementId = item.getAttribute('data-engagement-id');
      selectedClient.textContent = clientName;
      engagementInput.value = engagementId;
      closeDropdown();
    }

    function closeDropdown() {
      dropdownList.style.display = 'none';
      dropdownBtn.setAttribute('aria-expanded', 'false');
    }

    </script>
<!-- end dropdown menu -->

<!-- Script: Custom Tabs -->
 <script>
  // Tab switching logic for custom tabs
  document.querySelectorAll('.custom-tabs button').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetTab = btn.getAttribute('data-tab');
      if (!targetTab) return;

      // Remove active class on all buttons
      document.querySelectorAll('.custom-tabs button').forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
        b.setAttribute('tabindex', '-1');
      });

      // Add active class on clicked button
      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');
      btn.setAttribute('tabindex', '0');
      btn.focus();

      // Hide all tab panes
      document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));

      // Show target tab pane
      const activePane = document.getElementById(targetTab);
      if (activePane) activePane.classList.add('active');
    });
  });
</script>
<!-- end Script: Custom Tabs -->


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>