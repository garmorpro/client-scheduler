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

// Open modal for Manage Assignments or Add Entry (new modal)
  function openManageEntryModal(user_id, employeeName, weekStart, engagement_id = '') {
  console.log("Modal triggered with arguments:");
  console.log("user_id:", user_id);
  console.log("employeeName:", employeeName);
  console.log("weekStart:", weekStart);
  console.log("engagement_id:", engagement_id);

  const assignments = <?php echo json_encode($assignments); ?>;
  console.log("Assignments data loaded from PHP:", assignments);

  const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] ? assignments[user_id][weekStart] : [];
  console.log("Assignments for user and week:", assignmentsForWeek);

  const manageEntryModalElement = document.getElementById('manageEntryModal');
  if (!manageEntryModalElement) {
    console.warn("manageEntryModal element NOT found!");
  } else {
    console.log("manageEntryModal element found.");
  }
  const manageEntryModal = new bootstrap.Modal(manageEntryModalElement);

  // Set hidden inputs here for Add Assignment tab form:
  const manageUserIdInput = document.getElementById('manageModalUserId');
  const manageWeekInput = document.getElementById('manageModalWeek');
  const manageEngagementInput = document.getElementById('manageEngagementInput');

  if (manageUserIdInput) {
    manageUserIdInput.value = user_id;
    console.log("Set manageModalUserId to:", manageUserIdInput.value);
  } else {
    console.warn("manageModalUserId input NOT found!");
  }

  if (manageWeekInput) {
    manageWeekInput.value = weekStart;
    console.log("Set manageModalWeek to:", manageWeekInput.value);
  } else {
    console.warn("manageModalWeek input NOT found!");
  }

  if (manageEngagementInput) {
    manageEngagementInput.value = engagement_id;
    console.log("Set manageEngagementInput to:", manageEngagementInput.value);
  } else {
    console.warn("manageEngagementInput input NOT found!");
  }

  if (assignmentsForWeek.length > 0) {
    console.log("Assignments found for this user/week, rendering list and showing manage modal");
    renderAssignmentsList(user_id, weekStart);
    manageEntryModal.show();
  } else {
    console.log("No assignments found for this user/week, opening Add Entry modal");
    openAddEntryModal(user_id, employeeName, weekStart, engagement_id);
  }
}




// end Open modal for Manage Assignments or Add Entry

// Open Add Entry modal
    function openAddEntryModal(user_id, employeeName, weekStart, engagement_id = '', tab = 'assignment', suffix = '') {
  const suffixStr = suffix ? `-${suffix}` : '';

  // inputs
  const modalUserId = document.getElementById(`modalUserId${suffixStr}`);
  const modalWeek = document.getElementById(`modalWeek${suffixStr}`);
  const timeOFFuserId = document.getElementById(`timeOFFuser_id${suffixStr}`);
  const timeOFFweekStart = document.getElementById(`timeOFFweek_start${suffixStr}`);

  if (modalUserId) modalUserId.value = user_id;
  if (modalWeek) modalWeek.value = weekStart;
  if (timeOFFuserId) timeOFFuserId.value = user_id;
  if (timeOFFweekStart) timeOFFweekStart.value = weekStart;

  const modalEmployeeNameDisplay = document.getElementById(`modalEmployeeNameDisplay${suffixStr}`);
  if (modalEmployeeNameDisplay) modalEmployeeNameDisplay.textContent = employeeName;

    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    const date = new Date(weekStart);

    const day = date.getDay(); // 0 = Sunday, 1 = Monday, ..., 6 = Saturday

    // If Sunday (0), go forward 1 day to Monday
    // Else go back (day - 1) days to Monday
    const diffToMonday = (day === 0) ? 1 : 1 - day;

    const mondayDate = new Date(date);
    mondayDate.setDate(date.getDate() + diffToMonday);

    // Then update modalWeekDisplay
  const modalWeekDisplay = document.getElementById(`modalWeekDisplay${suffixStr}`);
  if (modalWeekDisplay) modalWeekDisplay.textContent = mondayDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

  if (tab === 'assignment') {
    const selectedClient = document.getElementById(`selectedClient${suffixStr}`);
    const engagementInputEl = document.getElementById(`engagementInput${suffixStr}`);
    const assignedHours = document.getElementById(`assignedHours${suffixStr}`);

    if (selectedClient) {
      selectedClient.textContent = 'Select a client';
      selectedClient.classList.add('text-muted');
    }

    console.log('Engagement ID passed to openAddEntryModal:', engagement_id);
    console.log('engagementInput element found:', engagementInputEl);
    if (engagementInputEl) {
      console.log('Before set, engagementInput value:', engagementInputEl.value);
      engagementInputEl.value = engagement_id;
      console.log('After set, engagementInput value:', engagementInputEl.value);
    }

    if (assignedHours) assignedHours.value = '';
  } else if (tab === 'timeoff') {
    const timeoffHours = document.getElementById(`timeoffHours${suffixStr}`);
    const timeoffReason = document.getElementById(`timeoffReason${suffixStr}`);

    if (timeoffHours) timeoffHours.value = '';
    if (timeoffReason) timeoffReason.value = '';
  }

  // Show modal by suffix as well
  const addEntryModalEl = document.getElementById(`addEntryModal${suffixStr}`);
  if (!addEntryModalEl) {
    console.warn(`Modal element addEntryModal${suffixStr} not found`);
    return;
  }
  const addEntryModal = new bootstrap.Modal(addEntryModalEl);
  addEntryModal.show();

  // Activate tab
  const tabSelector = tab === 'assignment'
    ? `.custom-tabs-modal button[data-tab="assignmentTabPane${suffixStr}"]`
    : `.custom-tabs-modal button[data-tab="timeoffTabPane${suffixStr}"]`;

  const tabTriggerEl = addEntryModalEl.querySelector(tabSelector);
  if (tabTriggerEl) {
    const tabInstance = bootstrap.Tab.getOrCreateInstance(tabTriggerEl);
    tabInstance.show();
  } else {
    console.warn('Tab trigger element not found:', tabSelector);
  }
}



// openEditModal remains unchanged
    function openEditModal(event) {
        const buttonElement = event.target;
        const assignmentId = buttonElement.getAttribute('data-assignment-id');
        const assignedHours = buttonElement.getAttribute('data-assigned-hours');

        document.getElementById('editAssignmentId').value = assignmentId;
        document.getElementById('editAssignedHours').value = assignedHours;

        const editModal = new bootstrap.Modal(document.getElementById('editAssignmentModal'));
        editModal.show();
    }
// end Open Add Entry modal


// Manage assignments modal
    function openManageAssignmentsModal(user_id, employeeName, weekStart) {
  const formattedDate = new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  document.getElementById('assignmentsModalTitle').innerText = `Manage Assignments for Week of ${formattedDate}`;
  document.getElementById('assignmentsModalSubheading').innerText = `Consultant: ${employeeName}`;

  // Render assignments list in manageEntryModal manageTabPane
  renderAssignmentsList(user_id, weekStart);

  const assignmentsModal = new bootstrap.Modal(document.getElementById('manageEntryModal'));
  assignmentsModal.show();
  }
// end Manage assignments modal


// Delete assignment function
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
                    location.reload();
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
// end Delete assignment function


// open assignement modal
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
// end open assignement modal


// open employee modal
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
// end open employee modal

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

                      
                          $assignmentsForWeek = $assignments[$userId][$weekKey] ?? [];
                          $cellContent = "";
                          $engagement_id = ''; // define early so it's always set

                          if (!empty($assignmentsForWeek)) {
                              $engagement_id = $assignmentsForWeek[0]['engagement_id'] ?? '';
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
                              <td class="addable <?php echo $tdClass; ?>" style="cursor:pointer;"
                                  onclick='openManageEntryModal(
                                      "<?php echo $userId; ?>",
                                      <?php echo json_encode($fullName); ?>,
                                      "<?php echo $weekKey; ?>", 
                                      "<?php echo $engagement_id; ?>"
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

<!-- WORKING ON -> Modal for Manage Entry -->
  <div class="modal fade" id="manageEntryModal" tabindex="-1" aria-labelledby="manageEntryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="manageEntryModalLabel">
          <i class="bi bi-pencil-square me-2"></i> Manage Entries
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Tabs -->
      <div class="custom-tabs-modal d-flex" role="tablist" style="border-bottom: 1px solid #dee2e6;">
        <button
          class="active btn btn-link flex-fill text-center text-decoration-none"
          data-tab="manageTabPane"
          role="tab"
          aria-selected="true"
          aria-controls="manageTabPane"
          tabindex="0"
          type="button"
        >
          Manage Assignments
        </button>
        <button
          class="btn btn-link flex-fill text-center text-decoration-none"
          data-tab="assignmentTabPane"
          role="tab"
          aria-selected="false"
          aria-controls="assignmentTabPane"
          tabindex="-1"
          type="button"
        >
          Add Assignment
        </button>
        <button
          class="btn btn-link flex-fill text-center text-decoration-none"
          data-tab="timeOffTabPane"
          role="tab"
          aria-selected="false"
          aria-controls="timeOffTabPane"
          tabindex="-1"
          type="button"
        >
          Manage Time Off
        </button>
      </div>

      <!-- Tab Content -->
      <div class="tab-content-modal p-3">
        <!-- Manage Assignments Tab -->
        <div
          id="manageTabPane"
          class="tab-pane active show"
          role="tabpanel"
          aria-labelledby="manageTab"
          aria-hidden="false"
        >
          <div id="assignmentsListContainer" class="mb-3">
            <!-- Assignment items will be rendered here -->
          </div>

        </div>


        <!-- Add Assignement Tab -->

        <div
          id="assignmentTabPane"
          class="tab-pane"
          role="tabpanel"
          aria-labelledby="assignmentTab"
          aria-hidden="true"
        >
          <form id="assignmentForm" action="add_assignment.php" method="POST">
            <!-- Hidden inputs -->
            <input type="text" id="manageModalUserId" name="user_id" value="">
            <input type="text" id="manageModalWeek" name="week_start" value="">

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

              <input type="text" id="manageEngagementInput" name="engagement_id" required>
            </div>

            <!-- Assigned hours -->
            <div class="mb-3">
              <label for="assignedHours" class="form-label">Hours</label>
              <input type="number" class="form-control" id="assignedHours" name="assigned_hours" min="0" step="0.25" required>
            </div>

            <div class="modal-footer p-0 pt-3 border-0">
              <button
                type="button"
                class="btn badge text-black p-2 text-decoration-none fw-medium"
                style="font-size: .875rem; box-shadow: inset 0 0 0 1px rgb(229,229, 229);"
                data-bs-dismiss="modal"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="badge text-white p-2 text-decoration-none fw-medium"
                style="font-size: .875rem; background-color: rgb(3,2,18); border:none !important;"
              >
                Submit
              </button>
            </div>
          </form>
        </div>

        <!-- Time Off Tab -->
        <div
          id="timeOffTabPane"
          class="tab-pane"
          role="tabpanel"
          aria-labelledby="timeOffTab"
          aria-hidden="true"
        >
          <!-- You can put any time off related content or controls here -->
          <p class="text-muted">Manage time off requests and details here.</p>
        </div>
      </div>

      <div class="modal-footer p-0 mt-3 border-0">
        <button
          type="button"
          class="btn badge text-black p-2 text-decoration-none fw-medium"
          style="font-size: .875rem; box-shadow: inset 0 0 0 1px rgb(229,229,229);"
          data-bs-dismiss="modal"
        >
          Cancel
        </button>
      </div>

    </div>
  </div>
  </div>


<!-- end Modal for Manage entry -->

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
          <form id="editAssignmentForm" action="update_assignment.php" method="POST">
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

<!-- Modal for Adding Entry -->
  <div class="modal fade" id="addEntryModal" tabindex="-1" aria-labelledby="addEntryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="addEntryModalLabel">
          <i class="bi bi-calendar-range me-2"></i> New Entry
          <br>
          <span class="text-muted" style="font-size: 12px; font-weight: 400; padding-top: 0;">
            For <strong><span id="modalEmployeeNameDisplay"></span></strong> during week of <strong><span id="modalWeekDisplay"></span></strong>
          </span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Custom Tabs -->
      <div class="custom-tabs-modal" role="tablist">
        <button
          class="active"
          data-tab="assignmentTabPane"
          role="tab"
          aria-selected="true"
          aria-controls="assignmentTabPane"
          tabindex="0"
        >
          Add Assignment
        </button>
        <button
          data-tab="timeoffTabPane"
          role="tab"
          aria-selected="false"
          aria-controls="timeoffTabPane"
          tabindex="-1"
        >
          Add Time Off
        </button>
      </div>

      <!-- Second Form (example) -->
<form id="assignmentForm-2" action="add_assignment.php" method="POST">
  <!-- Hidden inputs -->
  <input type="text" id="modalUserId-2" name="user_id" value="">
  <input type="text" id="modalWeek-2" name="week_start" value="">

  <!-- Client Dropdown -->
  <div class="mb-3 custom-dropdown">
    <label for="engagementInput-2" class="form-label">Client Name</label>
    <div
      class="dropdown-btn"
      id="dropdownBtn-2"
      tabindex="0"
      aria-haspopup="listbox"
      aria-expanded="false"
      role="combobox"
      aria-labelledby="selectedClient-2"
    >
      <span id="selectedClient-2" class="text-muted">Select a client</span>
      <span>&#9662;</span>
    </div>

    <div
      class="dropdown-list"
      id="dropdownList-2"
      role="listbox"
      tabindex="-1"
      aria-labelledby="selectedClient-2"
    >
      <!-- your PHP generated dropdown items here -->
    </div>

    <input type="text" id="engagementInput-2" name="engagement_id" required>
  </div>

  <!-- Assigned hours -->
  <div class="mb-3">
    <label for="assignedHours-2" class="form-label">Hours</label>
    <input type="number" class="form-control" id="assignedHours-2" name="assigned_hours" min="0" step="0.25" required>
  </div>

  <div class="modal-footer p-0 pt-3 border-0">
    <button
      type="button"
      class="btn badge text-black p-2 text-decoration-none fw-medium"
      style="font-size: .875rem; box-shadow: inset 0 0 0 1px rgb(229,229, 229);"
      data-bs-dismiss="modal"
    >
      Cancel
    </button>
    <button
      type="submit"
      class="badge text-white p-2 text-decoration-none fw-medium"
      style="font-size: .875rem; background-color: rgb(3,2,18); border:none !important;"
    >
      Submit
    </button>
  </div>
</form>


        <!-- Time Off Tab Pane -->
        <div
          id="timeoffTabPane"
          class="tab-pane"
          role="tabpanel"
          aria-labelledby="timeoffTab"
          aria-hidden="true"
        >
          <form id="timeoffForm" action="add_timeoff.php" method="POST">
            <input type="hidden" id="timeOFFuser_id" name="user_id" value="">
            <input type="hidden" id="timeOFFweek_start" name="week_start" value="">

            <div class="mb-3">
              <label for="timeoffHours" class="form-label">Hours</label>
              <input
                type="number"
                class="form-control"
                id="timeoffHours"
                name="hours"
                min="0"
                step="0.25"
                required
              >
            </div>

            <div class="mb-3">
              <label for="timeoffReason" class="form-label">Reason for Time Off (optional)</label>
              <textarea
                class="form-control"
                id="timeoffReason"
                name="reason"
                rows="3"
                placeholder="Optional"
              ></textarea>
            </div>

            <div class="modal-footer p-0 pt-3 border-0">
              <button
                type="button"
                class="btn badge text-black p-2 text-decoration-none fw-medium"
                style="font-size: .875rem; box-shadow: inset 0 0 0 1px rgb(229,229, 229);"
                data-bs-dismiss="modal"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="badge text-white p-2 text-decoration-none fw-medium"
                style="font-size: .875rem; background-color: rgb(3,2,18); border:none !important;"
              >
                Submit
              </button>
            </div>
          </form>

        </div>
      </div>

    </div>
  </div>
  </div>


<!-- end Adding Entry -->

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
  function setupDropdown(suffix = '') {
  const dropdownBtn = document.getElementById(`dropdownBtn${suffix}`);
  const dropdownList = document.getElementById(`dropdownList${suffix}`);
  const selectedClient = document.getElementById(`selectedClient${suffix}`);
  const engagementInput = document.getElementById(`engagementInput${suffix}`);

  if (!dropdownBtn || !dropdownList || !selectedClient || !engagementInput) {
    console.warn('Dropdown elements not found for suffix:', suffix);
    return;
  }

  dropdownBtn.addEventListener('click', () => {
    const isOpen = dropdownList.style.display === 'block';
    dropdownList.style.display = isOpen ? 'none' : 'block';
    dropdownBtn.setAttribute('aria-expanded', (!isOpen).toString());
  });

  dropdownBtn.addEventListener('keydown', (e) => {
    if (['ArrowDown', 'Enter', ' '].includes(e.key)) {
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
      if (['Enter', ' '].includes(e.key)) {
        e.preventDefault();
        selectClient(item);
      } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        const next = item.nextElementSibling || dropdownList.querySelector('.dropdown-item');
        next.focus();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        const prev = item.previousElementSibling || dropdownList.querySelector('.dropdown-item:last-child');
        prev.focus();
      } else if (e.key === 'Escape') {
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
    selectedClient.classList.remove('text-muted');
    engagementInput.value = engagementId;
    closeDropdown();
    console.log('Client selected:', clientName, 'engagement_id:', engagementId);
  }

  function closeDropdown() {
    dropdownList.style.display = 'none';
    dropdownBtn.setAttribute('aria-expanded', 'false');
  }

  // Expose a preselect function if needed:
  return {
    preselectClientByEngagementId(engagementId) {
      if (!engagementId) {
        selectedClient.textContent = 'Select a client';
        selectedClient.classList.add('text-muted');
        engagementInput.value = '';
        return;
      }
      const items = dropdownList.querySelectorAll('.dropdown-item');
      for (const item of items) {
        if (item.getAttribute('data-engagement-id') === engagementId) {
          selectedClient.textContent = item.getAttribute('data-client-name');
          selectedClient.classList.remove('text-muted');
          engagementInput.value = engagementId;
          return;
        }
      }
      // Not found
      selectedClient.textContent = 'Select a client';
      selectedClient.classList.add('text-muted');
      engagementInput.value = '';
    }
  };
}

// Initialize both dropdowns:
const dropdown1 = setupDropdown('');
const dropdown2 = setupDropdown('-2');

</script>

<!-- end dropdown menu -->

<!-- Script: Custom Tabs -->
  <script>
    // Generic Tab switching logic for all modals with custom tabs
    document.querySelectorAll('.custom-tabs-modal button').forEach(btn => {
      btn.addEventListener('click', () => {
        const modal = btn.closest('.modal');
        const targetTab = btn.getAttribute('data-tab');
        if (!targetTab || !modal) return;

        // Tab buttons within this modal
        const tabButtons = modal.querySelectorAll('.custom-tabs-modal button');
        tabButtons.forEach(b => {
          const isActive = (b === btn);
          b.classList.toggle('active', isActive);
          b.setAttribute('aria-selected', isActive ? 'true' : 'false');
          b.setAttribute('tabindex', isActive ? '0' : '-1');
        });

        // Tab panes within this modal
        const tabPanes = modal.querySelectorAll('.tab-content-modal > .tab-pane');
        tabPanes.forEach(pane => {
          const isActive = (pane.id === targetTab);
          pane.classList.toggle('active', isActive);
          pane.classList.toggle('show', isActive);
          pane.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });
      });
    });

    // Generic reset tabs on any modal close (for modals that have custom-tabs-modal)
    document.querySelectorAll('.modal').forEach(modalEl => {
      modalEl.addEventListener('hidden.bs.modal', () => {
        const tabsContainer = modalEl.querySelector('.custom-tabs-modal');
        if (!tabsContainer) return;

        const tabButtons = tabsContainer.querySelectorAll('button');
        if (tabButtons.length === 0) return;

        // Reset all tab buttons
        tabButtons.forEach(btn => {
          btn.classList.remove('active');
          btn.setAttribute('aria-selected', 'false');
          btn.setAttribute('tabindex', '-1');
        });

        // Activate first tab button
        tabButtons[0].classList.add('active');
        tabButtons[0].setAttribute('aria-selected', 'true');
        tabButtons[0].setAttribute('tabindex', '0');

        // Reset all tab panes inside this modal
        const tabPanes = modalEl.querySelectorAll('.tab-content-modal > .tab-pane');
        tabPanes.forEach(pane => {
          pane.classList.remove('active', 'show');
          pane.setAttribute('aria-hidden', 'true');
        });

        // Show first tab pane
        const firstTabId = tabButtons[0].getAttribute('data-tab');
        const firstPane = modalEl.querySelector(`#${firstTabId}`);
        if (firstPane) {
          firstPane.classList.add('active', 'show');
          firstPane.setAttribute('aria-hidden', 'false');
        }
      });
    });
  </script>
<!-- end Script: Custom Tabs -->

<!-- Script: Render Assignments Listing -->
  <script>
    function renderAssignmentsList(user_id, weekStart) {
  const assignments = <?php echo json_encode($assignments); ?>;
  const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] ? assignments[user_id][weekStart] : [];

  console.log('Assignments for user', user_id, 'week', weekStart, assignmentsForWeek);

  const container = document.getElementById('assignmentsListContainer');
  container.innerHTML = '';

  if (assignmentsForWeek.length === 0) {
    container.innerHTML = '<p class="text-muted">No assignments for this week.</p>';
    return;
  }

  assignmentsForWeek.forEach(assignment => {
    const card = document.createElement('div');
    card.classList.add('card', 'mb-3', 'shadow-sm');

    const cardBody = document.createElement('div');
    cardBody.classList.add('card-body', 'd-flex', 'justify-content-between', 'align-items-center');

    const leftDiv = document.createElement('div');
    leftDiv.innerHTML = `
      <div class="fw-semibold fs-6">${assignment.client_name || 'Unnamed Client'}</div>
      <small class="text-muted">Assigned Hours: ${assignment.assigned_hours || 0}</small>
    `;

    const rightDiv = document.createElement('div');

    // Edit link
    const editLink = document.createElement('a');
    editLink.href = "#";
    editLink.title = "Edit Assignment";
    editLink.className = "text-primary me-3";
    editLink.style = "font-size: 1.25rem; cursor: pointer; text-decoration: none;";
    editLink.innerHTML = `<i class="bi bi-pencil-square" style="font-size: 16px;"></i>`;
    editLink.onclick = (e) => {
      e.preventDefault();
      const parentModalEl = document.getElementById('manageEntryModal');
      const editModalEl = document.getElementById('editAssignmentModal');

      // Hide parent modal
      const parentModal = bootstrap.Modal.getInstance(parentModalEl) || new bootstrap.Modal(parentModalEl);
      parentModal.hide();

      // Show edit modal
      const editModal = new bootstrap.Modal(editModalEl);
      document.getElementById('editAssignmentId').value = assignment.assignment_id || assignment.id || '';
      document.getElementById('editAssignedHours').value = assignment.assigned_hours || 0;
      editModal.show();

      // When edit modal closes, re-show parent modal
      editModalEl.addEventListener('hidden.bs.modal', () => {
        parentModal.show();
      }, { once: true });
    };

    // Delete link
    const deleteLink = document.createElement('a');
    deleteLink.href = "#";
    deleteLink.title = "Delete Assignment";
    deleteLink.className = "text-danger";
    deleteLink.style = "font-size: 1.25rem; cursor: pointer; text-decoration: none;";
    deleteLink.innerHTML = `<i class="bi bi-trash" style="font-size: 16px;"></i>`;
    deleteLink.onclick = (e) => {
      e.preventDefault();
      console.log('Delete clicked for assignment:', assignment);
      // Your delete logic here (confirm and delete)
    };

    rightDiv.appendChild(editLink);
    rightDiv.appendChild(deleteLink);

    cardBody.appendChild(leftDiv);
    cardBody.appendChild(rightDiv);
    card.appendChild(cardBody);
    container.appendChild(card);
  });
  }

  </script>
<!-- end Script: Render Assignments Listing -->



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>