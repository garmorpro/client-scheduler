<?php
require_once '../includes/db.php';
session_start();

$isAdmin = isset($_SESSION['role']) && strtolower($_SESSION['user_role']) === 'admin';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// $today = date('Y-m-d');
// $startDate = isset($_GET['start']) ? date('Y-m-d', strtotime('previous monday', strtotime($_GET['start']))) : date('Y-m-d', strtotime('monday -2 weeks'));
// $endDate = date('Y-m-d', strtotime('+5 weeks', strtotime($startDate)));

// // Initialize mondays array
// $mondays = [];
// $current = strtotime($startDate);
// while ($current <= strtotime($endDate)) {
//     if (date('N', $current) == 1) {
//         $mondays[] = $current;
//     }
//     $current = strtotime('+1 week', $current);
// }


// Get current Monday
$today = strtotime('today');
$currentMonday = strtotime('monday this week', $today);

// Week offset from query (0 = current range)
$weekOffset = isset($_GET['week_offset']) ? intval($_GET['week_offset']) : 0;

// We want the current week in position 3 (index 2 of 0-based array)
// So we shift start date back 2 weeks from current week, then apply offset
$startMonday = strtotime("-2 weeks", $currentMonday);
$startMonday = strtotime("+{$weekOffset} weeks", $startMonday);

// Generate 7 Mondays
$mondays = [];
for ($i = 0; $i < 7; $i++) {
    $mondays[] = strtotime("+{$i} weeks", $startMonday);
}

// Display range for header
$firstWeek = reset($mondays);
$lastWeek = end($mondays);
$rangeLabel = "Week of " . date('n/j', $firstWeek) . " - Week of " . date('n/j', $lastWeek);














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

// ✅ Updated: Query assignments for the date range with engagement status
$query = "
    SELECT 
        a.assignment_id,
        a.user_id,
        a.engagement_id,
        e.client_name,
        a.week_start,
        a.assigned_hours,
        a.status,
        e.status AS engagement_status -- 👈 Add this line to get engagement's status
    FROM 
        assignments a
    JOIN 
        engagements e ON a.engagement_id = e.engagement_id
    WHERE 
        a.week_start BETWEEN ? AND ?
";

$stmt = $conn->prepare($query);
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
        'status' => $row['status'],
        'engagement_status' => $row['engagement_status'], // ✅ Now available
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Master Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">

    <style>
      body {
        font-size: 14px !important;
      }
        .form-select, .form-control { background-color: #f9fafb; border-radius: 8px; }
        .highlight-today { background-color: lightblue !important; }
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




    function openAddassignmentModal(user_id, employeeName, weekStart) {
        // Prepare modal data for engagement
        document.getElementById('modalEmployee').value = user_id;
        document.getElementById('modalEmployeeName').value = employeeName;
        document.getElementById('modalWeek').value = weekStart;

        const assignmentModalElement = new bootstrap.Modal(document.getElementById('assignmentModal'));
        assignmentModalElement.show();
    }

    function showAssignments(assignmentsForWeek) {
    let assignmentsList = '';

    assignmentsForWeek.forEach((assignment) => {
        assignmentsList += `
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div>
                    <strong>${assignment.client_name}</strong><br>
                    <small>${assignment.assigned_hours} hrs</small>
                </div>
                <div>
                    <button class="btn btn-sm btn-warning me-1" data-assignment-id="${assignment.assignment_id}" data-assigned-hours="${assignment.assigned_hours}" onclick="openEditModal(event)">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteAssignment(${assignment.assignment_id})">Delete</button>
                </div>
            </div>
        `;
    });

    document.getElementById('existingAssignments').innerHTML = assignmentsList;
}

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

        <a href="#" role="button" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18);" data-bs-toggle="modal" data-bs-target="#engagementModal" onclick="return false;">
            <i class="bi bi-plus-lg me-3"></i>New Engagement
        </a>

    </div>
</div>

    <div class="bg-white border rounded p-4 mb-4">
        <form id="filterForm" method="get" class="row g-3">
            <div class="col-md-9">
                <input type="text" id="searchInput" class="form-control" placeholder="Search projects, clients, or employees..." onkeyup="searchQuery()" />
                <div id="searchResults" class="dropdown-menu" style="max-height: 200px; overflow-y: auto; display:none;"></div>
            </div>

            <!-- <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Select a Statuses</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="pending">Pending</option>
                    <option value="not_confirmed">Not Confirmed</option>
                </select>
            </div> -->
            <!-- <div class="col-md-3 d-flex align-items-center gap-3">
                <input type="date" name="start" class="form-control" value="<?php //echo htmlspecialchars($startDate); ?>" onchange="autoSubmitDateFilter()">
                <a href="?start=<?php //echo date('Y-m-d', strtotime('sunday -2 weeks')); ?>" class="btn btn-outline-secondary">Today</a>
            </div> -->
        </form>


<div class="d-flex justify-content-center align-items-center mb-3">
    <a href="?week_offset=<?php echo $weekOffset - 1; ?>" class="btn btn-outline-secondary btn-sm me-2">
        &lt;
    </a>
    <span class="fw-semibold"><?php echo $rangeLabel; ?></span>
    <a href="?week_offset=<?php echo $weekOffset + 1; ?>" class="btn btn-outline-secondary btn-sm ms-2">
        &gt;
    </a>
</div>




        <script>
// document.addEventListener('DOMContentLoaded', () => {
//     let startMondayTs = <?php// echo $startMonday; ?>; // timestamp of first week

//     function loadWeeks() {
//         window.location.href = `master_schedule.php?start_monday=${startMondayTs}`;
//     }

//     document.getElementById('prevWeek').addEventListener('click', () => {
//         startMondayTs -= 7 * 24 * 60 * 60; // move 1 week earlier
//         loadWeeks();
//     });

//     document.getElementById('nextWeek').addEventListener('click', () => {
//         startMondayTs += 7 * 24 * 60 * 60; // move 1 week later
//         loadWeeks();
//     });
// });

          </script>



    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th class="text-start">Employee</th>
                    <?php foreach ($mondays as $monday): ?>
                        <?php 
                        $weekStart = date('Y-m-d', $monday);
                        $highlightClass = ($today >= $weekStart && $today < date('Y-m-d', strtotime('+7 days', $monday))) ? 'highlight-today' : '';
                        ?>
                        <th class="<?php echo $highlightClass; ?>">
                            <?php echo date('M j', $monday); ?><br>
                            <small class="text-muted">Week of <?php echo date('n/j', $monday); ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $userId => $employeeName): ?>
                    <tr>
                        <td class="text-start fw-semibold">
                            <?php echo htmlspecialchars($employees[$userId]['full_name']); ?>
                            <span class="text-muted text-capitalize" style="font-size: 12px;">&nbsp;&nbsp;<?php echo $employees[$userId]['role']; ?></span>
                        </td>

                        <?php foreach ($mondays as $monday): ?>
                            <?php 
                            $weekStart = date('Y-m-d', $monday);
                            $assignmentsForWeek = $assignments[$userId][$weekStart] ?? [];
                            $cellContent = "";
                            
                            if ($assignmentsForWeek) {
                                foreach ($assignmentsForWeek as $assignment) {
                                    $engagementStatus = strtolower($assignment['engagement_status'] ?? 'confirmed');

                                        switch ($engagementStatus) {
                                            case 'confirmed':
                                                $badgeColor = 'success';
                                                break;
                                            case 'pending':
                                                $badgeColor = 'purple';
                                                break;
                                            case 'not_confirmed':
                                                $badgeColor = 'primary';
                                                break;
                                            default:
                                                $badgeColor = 'secondary';
                                        }


                                        $cellContent .= "<span class='badge bg-$badgeColor'>{$assignment['client_name']} ({$assignment['assigned_hours']})</span><br>";

                                }
                            } else {
                                $cellContent = "<span class='text-muted'>+</span>";
                            }
                            ?>
                            <?php if ($isAdmin): ?>
                            <td class="addable" onclick='openManageOrAddModal(
                                "<?php echo $userId; ?>",
                                <?php echo json_encode($employees[$userId]['full_name']); ?>,
                                "<?php echo $weekStart; ?>"
                            )'>
                                    <?php echo $cellContent; ?>
                                </td>
                            <?php else: ?>
    <td>
        <?php echo $cellContent; ?>
    </td>
<?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

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



<!-- Modal for Adding assignment -->
<div class="modal fade" id="assignmentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Engagement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="engagementForm" action="add_assignment.php" method="POST">
          <input type="hidden" id="modalEmployee" name="employee">
          <input type="hidden" id="modalEmployeeName">
          <input type="hidden" id="modalWeek" name="week_start">
          <input type="hidden" id="modalEngagementId" name="engagement_id">

          <div class="mb-3">
            <label for="client_name" class="form-label">Client Name</label>
            <select class="form-select" id="client_name" name="client_name" required>
              <option value="" disabled selected>Select a client</option>
              <?php foreach ($activeClients as $client): ?>
                <option value="<?php echo $client['engagement_id']; ?>">
                  <?php echo htmlspecialchars($client['client_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>



          <div class="mb-3">
            <label for="numberOfWeeks" class="form-label">Number of Weeks</label>
            <input type="number" class="form-control" id="numberOfWeeks" name="numberOfWeeks" min="1" onchange="generateWeekInputs()" required>
          </div>

          <div id="weeksContainer"></div>

          <div class="mb-3 text-end">
            <button type="submit" id="modalSubmitBtn" class="btn btn-primary">Add Assignment</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


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


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>