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
    $assignments[$row['user_id']][$row['week_start']] = [
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

    function openModal(userId, employeeName, weekStart, engagementId = null) {
        document.getElementById('modalEmployee').value = user_id;
        document.getElementById('modalEmployeeName').value = employeeName;
        document.getElementById('modalWeek').value = weekStart;
        document.getElementById('modalEngagementId').value = engagementId ?? '';

        document.getElementById('client_name').selectedIndex = 0;
        document.getElementById('numberOfWeeks').value = '';
        document.getElementById('weeksContainer').innerHTML = '';

        document.getElementById('modalTitle').innerText = engagementId ? 'Edit Engagement' : 'Add Engagement';
        document.getElementById('modalSubmitBtn').innerText = engagementId ? 'Save Changes' : 'Add Engagement';

        const modalElement = new bootstrap.Modal(document.getElementById('engagementModal'));
        modalElement.show();
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
                            $data = $assignments[$userId][$weekStart] ?? null;
                            $cellContent = $data ? "<span>{$data['client_name']} ({$data['assigned_hours']})</span>" : "<span class='text-muted'>+</span>";
                            $engagementId = $data['engagement_id'] ?? null;
                            ?>
                            <td class="addable" onclick="openModal('<?php echo $userId; ?>', '<?php echo htmlspecialchars($employeeName); ?>', '<?php echo $weekStart; ?>', '<?php echo $engagementId ?? 'null'; ?>')">
                                <?php echo $cellContent; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="engagementModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Engagement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="engagementForm" action="add-engagement-process.php" method="POST">
          <input type="text" id="modalEmployee" name="employee">
          <input type="text" id="modalEmployeeName">
          <input type="text" id="modalWeek" name="week_start">
          <input type="text" id="modalEngagementId" name="engagement_id">

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