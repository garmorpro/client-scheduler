<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$today = date('Y-m-d');
$startDate = isset($_GET['start']) ? date('Y-m-d', strtotime('previous monday', strtotime($_GET['start']))) : date('Y-m-d', strtotime('monday -3 weeks'));
$endDate = date('Y-m-d', strtotime('+5 weeks', strtotime($startDate)));

$mondays = [];
$current = strtotime($startDate);
while ($current <= strtotime($endDate)) {
    if (date('N', $current) == 1) {
        $mondays[] = $current;
    }
    $current = strtotime('+1 week', $current);
}

$employees = ['John Doe', 'Jane Smith', 'Alex Johnson'];

$clientQuery = "SELECT engagement_id, client_name FROM engagements WHERE status = 'active'";
$clientResult = $conn->query($clientQuery);
if ($clientResult === false) {
    die('MySQL query failed: ' . $conn->error);
}

$activeClients = [];
while ($clientRow = $clientResult->fetch_assoc()) {
    $activeClients[] = $clientRow;
}

$query = "
    SELECT 
        aw.assignment_id, 
        e.client_name, 
        aw.assigned_hours, 
        aw.week_start, 
        e.engagement_id, 
        a.user_id
    FROM 
        assignment_weeks aw
    JOIN 
        assignments a ON a.assignment_id = aw.assignment_id
    JOIN 
        engagements e ON e.engagement_id = a.engagement_id
    WHERE 
        aw.week_start <= ? AND aw.week_start >= ? AND a.user_id = ?
    ORDER BY 
        aw.week_start
";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('MySQL prepare failed: ' . $conn->error);
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

        function openModal(employee, weekStart, engagementId = null) {
            document.getElementById('modalEmployee').value = employee;
            document.getElementById('modalWeek').value = weekStart;
            document.getElementById('modalEngagementId').value = engagementId || '';
            document.getElementById('modalTitle').innerText = 'Add Engagement';
            document.getElementById('modalSubmitBtn').innerText = 'Add Engagement';
            new bootstrap.Modal(document.getElementById('engagementModal')).show();
        }

        function generateWeekInputs() {
            var numberOfWeeks = document.getElementById("numberOfWeeks").value;
            var weeksContainer = document.getElementById("weeksContainer");
            weeksContainer.innerHTML = '';
            for (var i = 1; i <= numberOfWeeks; i++) {
                var group = document.createElement("div");
                group.classList.add("mb-3");
                group.innerHTML = `
                    <label class="form-label">Week ${i}</label>
                    <input type="date" class="form-control mb-2" name="week_start_${i}" required>
                    <input type="number" class="form-control" name="assigned_hours_${i}" placeholder="Assigned Hours" required>
                `;
                weeksContainer.appendChild(group);
            }
        }
    </script>
</head>
<body class="d-flex">
<?php include_once '../templates/sidebar.php'; ?>
<div class="flex-grow-1 p-4">
    <h3 class="mb-0">Master Schedule</h3>
    <p class="text-muted mb-4">Complete overview of all client engagements and team assignments</p>
    <!-- ... Filter + Table (unchanged) ... -->
</div>

<!-- Modal -->
<div class="modal fade" id="engagementModal" tabindex="-1" aria-labelledby="engagementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Engagement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="add-engagement-process.php" method="POST">
                    <input type="hidden" id="modalEmployee" name="employee">
                    <input type="hidden" id="modalWeek" name="default_week">
                    <input type="hidden" id="modalEngagementId" name="engagement_id">

                    <div class="mb-3">
                        <label for="client_name" class="form-label">Client Name</label>
                        <select class="form-select" id="client_name" name="client_name" required>
                            <option value="" selected disabled>Select a client</option>
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

                    <div class="mb-3">
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
