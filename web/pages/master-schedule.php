<?php
require_once '../includes/db.php'; // Your DB connection file
session_start();
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
$userQuery = "SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name FROM users WHERE status = 'active'";
$userResult = $conn->query($userQuery);
if ($userResult) {
    while ($userRow = $userResult->fetch_assoc()) {
        $employees[$userRow['user_id']] = $userRow['full_name'];
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
        a.status  -- ✅ Add this line
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

    
    
    // open modal

    function openModal(user_id, employeeName, weekStart, engagementId = null) {
    // Prepare modal data for engagement
    document.getElementById('modalEmployee').value = user_id;
    document.getElementById('modalEmployeeName').value = employeeName;
    document.getElementById('modalWeek').value = weekStart;
    document.getElementById('modalEngagementId').value = engagementId ?? '';

    // Fetch assignments
    const assignments = <?php echo json_encode($assignments); ?>;
    const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] ? assignments[user_id][weekStart] : [];

    // Hide existing modals first before showing a new one
    const assignmentsModal = new bootstrap.Modal(document.getElementById('assignmentsModal'));
    assignmentsModal.hide(); // Close the Manage Assignments modal

    if (assignmentsForWeek.length > 0) {
        // Manage assignments modal
        const formattedDate = new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        document.getElementById('assignmentsModalTitle').innerText = `Manage Assignments for Week of ${formattedDate}`;
        document.getElementById('assignmentsModalSubheading').innerText = `Consultant: ${employeeName}`;

        showAssignments(assignmentsForWeek, user_id, weekStart, employeeName);

        // Show the Manage Assignments modal
        assignmentsModal.show();
    } else {
        // Engagement modal (no existing assignments)
        const engagementModalElement = new bootstrap.Modal(document.getElementById('engagementModal'));
        engagementModalElement.show();
    }
}






    function showAssignments(assignmentsForWeek, user_id, weekStart, employeeName) {
    let assignmentsList = '';

    assignmentsForWeek.forEach((assignment, index) => {
        assignmentsList += `
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div>
                    <strong>${assignment.client_name}</strong><br>
                    <small>${assignment.assigned_hours} hrs</small>
                </div>
                <div>
                    <button class="btn btn-sm btn-warning me-1" onclick="openEditModal(${assignment.assignment_id}, '${assignment.assigned_hours}')">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteAssignment(${assignment.engagement_id})">Delete</button>
                </div>
            </div>
        `;
    });

    // Add "Add Assignment" button if there are existing assignments
    if (assignmentsForWeek.length > 0) {
        assignmentsList += `
            <button class="btn btn-sm btn-success mt-2" onclick="openModal('${user_id}', '${employeeName}', '${weekStart}')">
                <i class="bi bi-plus-circle"></i> Add Assignment
            </button>
        `;
    }

    document.getElementById('existingAssignments').innerHTML = assignmentsList;
}


function openEditModal(assignmentId, assignedHours) {
    document.getElementById('editAssignmentId').value = assignmentId;
    document.getElementById('editAssignedHours').value = assignedHours;

    const editModal = new bootstrap.Modal(document.getElementById('editAssignmentModal'));
    editModal.show();
}



    function editAssignment(engagementId) {
        // Logic for editing an assignment
        console.log('Edit assignment:', engagementId);
        // You can populate the modal with existing data here for editing
    }

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
                location.reload(); // Reload the page or re-fetch assignments
            } else {
                alert('Failed to delete assignment.');
            }
        });
    }
}


    function generateWeekInputs() {
        const numberOfWeeks = parseInt(document.getElementById("numberOfWeeks").value) || 0;
        const weeksContainer = document.getElementById("weeksContainer");
        weeksContainer.innerHTML = '';

        for (let i = 1; i <= numberOfWeeks; i++) {
            const inputGroup = document.createElement("div");
            inputGroup.classList.add("mb-3");
            inputGroup.innerHTML = `
                <label class="form-label">Week ${i} Start</label>
                <input type="date" class="form-control" name="week_start_${i}" required>
                <label class="form-label mt-2">Assigned Hours</label>
                <input type="number" class="form-control" name="assigned_hours_${i}" placeholder="e.g. 20" min="1" required>
            `;
            weeksContainer.appendChild(inputGroup);
        }
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
                <input type="text" name="search" class="form-control" placeholder="Search projects or clients...">
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
                    <td class="text-start fw-semibold"><?php echo htmlspecialchars($employeeName); ?></td>
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
                        <td class="addable" onclick="openModal('<?php echo $userId; ?>', '<?php echo htmlspecialchars($employeeName); ?>', '<?php echo $weekStart; ?>')">
                            <?php echo $cellContent; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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

<!-- Edit Assignment Modal -->
<div class="modal fade" id="editAssignmentModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="editAssignmentForm" action="edit-assignment-process.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Assignment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="assignment_id" id="editAssignmentId">
        <div class="mb-3">
          <label for="editAssignedHours" class="form-label">Assigned Hours</label>
          <input type="number" class="form-control" name="assigned_hours" id="editAssignedHours" min="1" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
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

          <div id="existingAssignments"></div>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
