<?php
require_once '../includes/db.php'; // or wherever your DB connection is
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

// Modify the query to join 'assignments', 'assignment_weeks', and 'engagements' based on 'assignment_id' and 'engagement_id'
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
        assignments a ON a.assignment_id = aw.assignment_id  -- join on assignment_id
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
            console.log('openModal called', employee, weekStart, engagementId); // Debugging
                
            document.getElementById('modalEmployee').value = employee;
            document.getElementById('modalWeek').value = weekStart;
            if (engagementId) {
                document.getElementById('modalEngagementId').value = engagementId;
                document.getElementById('modalTitle').innerText = 'Edit Engagement';
                document.getElementById('modalSubmitBtn').innerText = 'Save Changes';
            } else {
                document.getElementById('modalTitle').innerText = 'Add Engagement';
                document.getElementById('modalSubmitBtn').innerText = 'Add Engagement';
            }
          
            // Fallback for modal trigger
            const modalElement = new bootstrap.Modal(document.getElementById('engagementModal'));
            modalElement.show();
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
                                        $cellContent = "<span onclick=\"openModal('{$employee}', '{$weekStart}', '{$row['engagement_id']}')\">{$row['client_name']} ({$row['assigned_hours']})</span>";
                                        $engagementId = $row['engagement_id'];
                                        break;
                                    }
                                }
                                if ($cellContent === '-') {
                                    $cellContent = "<span class='text-muted' onclick=\"openModal('{$employee}', '{$weekStart}')\">+</span>";
                                }
                            ?>
                            <td class="addable"><?php echo $cellContent; ?></td>
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
                    <form action="add-engagement-process.php" method="POST">
                        <input type="hidden" id="modalEmployee" name="employee" value="">
                        <input type="hidden" id="modalWeek" name="week_start" value="">
                        <input type="hidden" id="modalEngagementId" name="engagement_id" value="">

                        <div class="mb-3">
                            <label for="client_name" class="form-label">Client Name</label>
                            <input type="text" class="form-control" id="client_name" name="client_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="assigned_hours" class="form-label">Assigned Hours</label>
                            <input type="number" class="form-control" id="assigned_hours" name="assigned_hours" required>
                        </div>

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
