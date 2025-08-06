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

// Initialize employees array (you can fetch this from your database or use static values)
$employees = ['John Doe', 'Jane Smith', 'Alex Johnson']; // Replace this with actual data if necessary

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

// Modify the query to join 'assignments', 'assignment_weeks', and 'engagements' based on 'assignment_id' and 'engagement_id'
$query = "
    SELECT 
        e.client_name, 
        aw.assigned_hours, 
        aw.week_start, 
        e.engagement_id, 
        a.user_id
    FROM 
        assignment_weeks aw
    JOIN 
        engagements e ON e.engagement_id = a.engagement_id
    WHERE 
        aw.week_start <= ? AND aw.week_start >= ? AND a.user_id = ?
    ORDER BY 
        aw.week_start
";

$stmt = $conn->prepare($query);

if ($stmt === false) {
    // If prepare fails, output the error and exit
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
  console.log('openModal called', employee, weekStart, engagementId);

  document.getElementById('modalEmployee').value = employee;
  document.getElementById('modalWeek').value = weekStart;
  document.getElementById('modalEngagementId').value = engagementId ?? '';

  document.getElementById('client_name').selectedIndex = 0; // Reset client dropdown
  document.getElementById('numberOfWeeks').value = ''; // Reset number of weeks
  document.getElementById('weeksContainer').innerHTML = ''; // Clear inputs

  document.getElementById('modalTitle').innerText = engagementId ? 'Edit Engagement' : 'Add Engagement';
  document.getElementById('modalSubmitBtn').innerText = engagementId ? 'Save Changes' : 'Add Engagement';

  const modalElement = new bootstrap.Modal(document.getElementById('engagementModal'));
  modalElement.show();
}

function generateWeekInputs() {
  const numberOfWeeks = parseInt(document.getElementById("numberOfWeeks").value) || 0;
  const weeksContainer = document.getElementById("weeksContainer");

  weeksContainer.innerHTML = ''; // Clear old inputs

  for (let i = 1; i <= numberOfWeeks; i++) {
    const inputGroup = document.createElement("div");
    inputGroup.classList.add("mb-3");

    inputGroup.innerHTML = `
      <label for="week_start_${i}" class="form-label">Week ${i} Start</label>
      <input type="date" class="form-control" id="week_start_${i}" name="week_start_${i}" required>

      <label for="assigned_hours_${i}" class="form-label mt-2">Assigned Hours</label>
      <input type="number" class="form-control" id="assigned_hours_${i}" name="assigned_hours_${i}" placeholder="e.g. 20" min="1" required>
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
                    <input type="text" name="search" class="form-control" placeholder="Search projects or clients..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="on_hold" <?php echo (isset($_GET['status']) && $_GET['status'] == 'on_hold') ? 'selected' : ''; ?>>On Hold</option>
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
                        <?php foreach ($mondays as $monday): 
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
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td class="text-start fw-semibold"><?php echo htmlspecialchars($employee); ?></td>
                            <?php foreach ($mondays as $monday): 
                                $weekStart = date('Y-m-d', $monday);
                                $stmt->bind_param('sss', $weekStart, $weekStart, $employee);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $cellContent = '-';
                                $engagementId = null;
                                while ($row = $result->fetch_assoc()) {
                                    if ($row['user_id'] === $employee && $row['week_start'] <= $weekStart && $row['week_start'] >= $weekStart) {
                                        $cellContent = "<span>{$row['client_name']} ({$row['assigned_hours']})</span>";
                                        $engagementId = $row['engagement_id'];
                                        break;
                                    }
                                }
                                if ($cellContent === '-') {
                                    $cellContent = "<span class='text-muted'>+</span>";
                                }
                            ?>
                            <td class="addable" onclick="openModal('<?php echo $employee; ?>', '<?php echo $weekStart; ?>', '<?php echo $engagementId ? $engagementId : 'null'; ?>')">
                                <?php echo $cellContent; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- Modal for Adding and Editing Engagement -->
<div class="modal fade" id="engagementModal" tabindex="-1" aria-labelledby="engagementModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Engagement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="engagementForm" action="add-engagement-process.php" method="POST">
          <input type="hidden" id="modalEmployee" name="employee">
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



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
