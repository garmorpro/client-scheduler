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

        // Open modal for Manage Assignments or Add Engagement
        function openManageOrAddModal(user_id, employeeName, weekStart) {
            console.log(`Opening modal for User: ${user_id}, Week: ${weekStart}`);
            const assignments = <?php echo json_encode($assignments); ?>;
            const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] ? assignments[user_id][weekStart] : [];

            if (assignmentsForWeek.length > 0) {
                const manageAddModal = new bootstrap.Modal(document.getElementById('manageAddModal'));
                manageAddModal.show();

                document.getElementById('manageAssignmentsButton').onclick = function() {
                    openManageAssignmentsModal(user_id, employeeName, weekStart);
                };
                document.getElementById('addAssignmentsButton').onclick = function() {
                    openAddEngagementModal(user_id, employeeName, weekStart);
                };
            } else {
                openAddEngagementModal(user_id, employeeName, weekStart);
            }
        }

        function openManageAssignmentsModal(user_id, employeeName, weekStart) {
            const formattedDate = new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            document.getElementById('assignmentsModalTitle').innerText = `Manage Assignments for Week of ${formattedDate}`;
            document.getElementById('assignmentsModalSubheading').innerText = `Consultant: ${employeeName}`;

            const assignments = <?php echo json_encode($assignments); ?>;
            const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] ? assignments[user_id][weekStart] : [];
            showAssignments(assignmentsForWeek);

            const assignmentsModal = new bootstrap.Modal(document.getElementById('assignmentsModal'));
            assignmentsModal.show();
        }

        function openAddEngagementModal(user_id, employeeName, weekStart) {
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

        function openEditModal(event) {
            const buttonElement = event.target;
            const assignmentId = buttonElement.getAttribute('data-assignment-id');
            const assignedHours = buttonElement.getAttribute('data-assigned-hours');

            const assignments = <?php echo json_encode($assignments); ?>;
            let currentStatus = 'pending';
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
    </script>

</head>
<body class="d-flex">
<?php include_once '../templates/sidebar.php'; ?>
<div class="flex-grow-1 p-4">
    <h3 class="mb-0">Master Schedule</h3>
    <p class="text-muted mb-4">Complete overview of all client engagements and team assignments</p>

    <!-- Filter Form, Search, and Table (unchanged) -->

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

<!-- Modal for Adding Engagement -->
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
                                <option value="<?php echo $client['engagement_id']; ?>"><?php echo htmlspecialchars($client['client_name']); ?></option>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
