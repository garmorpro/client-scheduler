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
        a.user_id,
        a.engagement_id,
        e.client_name,
        a.week_start,
        a.assigned_hours
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
        'client_name' => $row['client_name'],
        'assigned_hours' => $row['assigned_hours'],
        'engagement_id' => $row['engagement_id']
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

    function openModal(user_id, employeeName, weekStart, engagementId = null) {
        document.getElementById('modalEmployee').value = user_id;
        document.getElementById('modalEmployeeName').value = employeeName;
        document.getElementById('modalWeek').value = weekStart;
        document.getElementById('modalEngagementId').value = engagementId ?? '';

        // Fetch assignments for the specific user and week from PHP
        const assignments = <?php echo json_encode($assignments); ?>;
        const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] ? assignments[user_id][weekStart] : [];

        // Check if there are existing assignments
        if (assignmentsForWeek.length > 0) {
            // If there are existing assignments, show them in the new assignments modal
            document.getElementById('assignmentsModalTitle').innerText = 'Manage Assignments';
            showAssignments(assignmentsForWeek, weekStart, employeeName);
            const assignmentsModalElement = new bootstrap.Modal(document.getElementById('assignmentsModal'));
            assignmentsModalElement.show();
        } else {
            // If no assignments, show the form to add a new engagement
            document.getElementById('modalTitle').innerText = 'Add Engagement';
            document.getElementById('modalSubmitBtn').innerText = 'Add Engagement';
            document.getElementById('client_name').selectedIndex = 0;
            document.getElementById('numberOfWeeks').value = '';
            document.getElementById('weeksContainer').innerHTML = '';
            const modalElement = new bootstrap.Modal(document.getElementById('engagementModal'));
            modalElement.show();
        }
    }

    function showAssignments(assignmentsForWeek, weekStart, employeeName) {
        let assignmentsList = '';
        document.getElementById('assignmentsModalHeader').innerHTML = `Manage assignments for week of ${weekStart} <br> Consultant: ${employeeName}`;
        
        assignmentsForWeek.forEach((assignment) => {
            assignmentsList += `
                <p>
                    Client: ${assignment.client_name}, Hours: ${assignment.assigned_hours}
                    <button class="btn btn-warning btn-sm" onclick="editAssignment(${assignment.engagement_id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteAssignment(${assignment.engagement_id})">Delete</button>
                </p>`;
        });

        // Display existing assignments in the modal
        document.getElementById('existingAssignments').innerHTML = assignmentsList;
    }

    function editAssignment(engagementId) {
        // Logic for opening the edit modal
        console.log('Edit assignment:', engagementId);
        // For now, we can load the data into a new modal for editing
        const editModalElement = new bootstrap.Modal(document.getElementById('editAssignmentModal'));
        editModalElement.show();
    }

    function deleteAssignment(engagementId) {
        // Logic for deleting an assignment
        console.log('Delete assignment:', engagementId);
        // Call a PHP script to delete the assignment from the database
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
                <a href="?start=<?php echo date('Y-m-d', strtotime('monday -3 weeks')); ?>" class="btn btn-outline-secondary">Today</a>
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
                                $cellContent .= "<span>{$assignment['client_name']} ({$assignment['assigned_hours']})</span><br>";
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
        <h5 class="modal-title" id="assignmentsModalTitle">Manage Assignments</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="assignmentsModalHeader"></div>
        <div id="existingAssignments"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal for Editing an Assignment -->
<div class="modal fade" id="editAssignmentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Assignment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Your form for editing assignment -->
        <form>
            <div class="mb-3">
                <label for="editClientName" class="form-label">Client Name</label>
                <input type="text" class="form-control" id="editClientName" required>
            </div>
            <div class="mb-3">
                <label for="editAssignedHours" class="form-label">Assigned Hours</label>
                <input type="number" class="form-control" id="editAssignedHours" required>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
