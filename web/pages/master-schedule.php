<?php
require_once '../includes/db.php'; // Your DB connection file
session_start();

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$today = date('Y-m-d');
$startDate = isset($_GET['start']) ? date('Y-m-d', strtotime('previous monday', strtotime($_GET['start']))) : date('Y-m-d', strtotime('monday -3 weeks'));
$endDate = date('Y-m-d', strtotime('+5 weeks', strtotime($startDate)));

// Initialize mondays array
$mondays = [];
$current = strtotime($startDate);
while ($current <= strtotime($endDate)) {
    if (date('N', $current) == 1) {
        $mondays[] = $current;
    }
    $current = strtotime('+1 week', $current);
}

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
$clientQuery = "SELECT engagement_id, client_name FROM engagements WHERE status = 'active'";
$clientResult = $conn->query($clientQuery);

if ($clientResult === false) {
    die('MySQL query failed: ' . $conn->error);
}

$activeClients = [];
while ($clientRow = $clientResult->fetch_assoc()) {
    $activeClients[] = $clientRow;
}

// Query assignments for the date range
$query = "
    SELECT 
        a.assignment_id,
        a.user_id,
        a.engagement_id,
        e.client_name,
        a.week_start,
        a.assigned_hours,
        a.status
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
    // Store multiple assignments for each employee and week
    $assignments[$row['user_id']][$row['week_start']][] = [
        'assignment_id' => $row['assignment_id'],
        'client_name' => $row['client_name'],
        'assigned_hours' => $row['assigned_hours'],
        'engagement_id' => $row['engagement_id'],
        'status' => $row['status']
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Master Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
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
                openAddEngagementModal(user_id, employeeName, weekStart);
            };
        } else {
            // If no existing assignments, directly show Add Engagement modal
            openAddEngagementModal(user_id, employeeName, weekStart);
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




    function openAddEngagementModal(user_id, employeeName, weekStart) {
        // Prepare modal data for engagement
        document.getElementById('modalEmployee').value = user_id;
        document.getElementById('modalEmployeeName').value = employeeName;
        document.getElementById('modalWeek').value = weekStart;

        const engagementModalElement = new bootstrap.Modal(document.getElementById('engagementModal'));
        engagementModalElement.show();
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

    // Grab status from assignment array (parsed already in page)
    const assignments = <?php echo json_encode($assignments); ?>;
    let currentStatus = 'pending';

    // Try to find the status from the DOM context
    const parent = buttonElement.closest('.d-flex');
    if (parent) {
        const text = parent.innerHTML;
        if (text.includes('Confirmed')) currentStatus = 'confirmed';
        else if (text.includes('Not Confirmed')) currentStatus = 'not_confirmed';
        else currentStatus = 'pending';
    }

    document.getElementById('editAssignmentId').value = assignmentId;
    document.getElementById('editAssignedHours').value = assignedHours;
    document.getElementById('editStatus').value = currentStatus;

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



function openEngagementModal(engagementId) {
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


            // Set the progress bar width based on the assigned hours
            let utilizationPercent = totalAvailableHours > 0
                ? (totalAssignedHours / totalAvailableHours) * 100
                : 0;

            document.getElementById('utilizationBar').style.width = utilizationPercent + "%";
            document.getElementById('utilizationBar').setAttribute('aria-valuenow', totalAssignedHours);
            document.getElementById('utilizationBar').setAttribute('aria-valuemax', totalAvailableHours);


            // Set assigned employees
            let assignedEmployees = data.assigned_employees;
            document.getElementById('assignedEmployees').innerHTML = assignedEmployees;

            // Set client notes
            const notes = data.notes?.trim();
            document.getElementById('clientNotes').innerText = notes ? notes : "No notes available.";


            // Show modal
            const engagementModal = new bootstrap.Modal(document.getElementById('clientDetailsModal'));
            engagementModal.show();
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

    <style>
    .bg-purple {
        background-color: #6f42c1 !important;
        color: white;
    }
</style>
</head>
<body class="d-flex">
<?php include_once '../templates/sidebar.php'; ?>
<div class="flex-grow-1 p-4">
    <h3 class="mb-0">Master Schedule</h3>
    <p class="text-muted mb-4">Complete overview of all client engagements and team assignments</p>

    <div class="bg-white border rounded p-4 mb-4">
        <form id="filterForm" method="get" class="row g-3">
            <div class="col-md-7">
                <input type="text" id="searchInput" class="form-control" placeholder="Search projects, clients, or employees..." onkeyup="searchQuery()" />
                <div id="searchResults" class="dropdown-menu" style="max-height: 200px; overflow-y: auto; display:none;"></div>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="on_hold">On Hold</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-center gap-3">
                <input type="date" name="start" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>" onchange="autoSubmitDateFilter()">
                <a href="?start=<?php echo date('Y-m-d', strtotime('monday -2 weeks')); ?>" class="btn btn-outline-secondary">Today</a>
            </div>
        </form>
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
                                    $status = strtolower($assignment['status']);

                                    if ($status === 'confirmed') {
                                        $badgeColor = 'success'; // Green
                                    } elseif ($status === 'pending') {
                                        $badgeColor = 'purple'; // Custom class
                                    } elseif ($status === 'not_confirmed') {
                                        $badgeColor = 'primary'; // Blue
                                    } else {
                                        $badgeColor = 'primary'; // Default
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
          <div class="mb-3">
              <label for="editStatus" class="form-label">Status</label>
              <select class="form-select" id="editStatus" name="status" required>
                  <option value="confirmed">Confirmed</option>
                  <option value="pending">Pending</option>
                  <option value="not_confirmed">Not Confirmed</option>
              </select>
          </div>
          <div class="mb-3 text-end">
            <button type="submit" id="editSubmitBtn" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


<!-- Modal for Adding Engagements -->
<div class="modal fade" id="engagementModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Engagement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="engagementForm" action="add-engagement-process.php" method="POST">
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
            <button type="submit" id="modalSubmitBtn" class="btn btn-primary">Add Engagement</button>
          </div>
        </form>
      </div>
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
        <h3 id="clientName" class="text-center mb-3  fw-bold"></h3>

        <!-- Utilization Progress Bar -->
        <div class="mb-4">
          <h6>Total Assigned Hours</h6>
          <div class="d-flex justify-content-between">
            <span id="totalAssignedHours" class="fw-bold fs-5 text-dark"></span>
            <span id="totalAvailableHours" class="text-muted">/ <span id="totalHours">1000</span> hrs</span>
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

        <!-- Notes Section (Optional) -->
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
        openEmployeeModal(id);
    } else if (type === 'client') {
        openEngagementModal(id);

    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>