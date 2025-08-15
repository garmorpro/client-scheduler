<?php
require_once '../includes/db.php';
session_start();

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isManager = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'manager';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all employees
$employees = [];
$userQuery = "
    SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name, role
    FROM users
    WHERE status = 'active'
      AND role IN ('staff','senior')
    ORDER BY CASE WHEN role='senior' THEN 1 WHEN role='staff' THEN 2 END, first_name ASC
";
$userResult = $conn->query($userQuery);
if ($userResult) {
    while ($userRow = $userResult->fetch_assoc()) {
        $employees[$userRow['user_id']] = [
            'full_name' => $userRow['full_name'],
            'role' => $userRow['role']
        ];
    }
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
        .timeoff-cell { background-color: rgb(217,217,217); }
        <?php if ($isAdmin): ?>
        .timeoff-cell:hover { background-color: #e0f7fa; }
        <?php endif; ?>
        .timeoff-corner { position:absolute; top:2px; right:6px; font-size:.50rem; }
        .timeoff-card { border:2px dashed rgb(209,226,159); background: rgb(246,249,236);}
        .highlight-today { background-color: #ffeeba !important; }
    </style>
</head>
<body class="d-flex">
<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">Master Schedule</h3>
            <p class="text-muted mb-0">Complete overview of all client engagements and team assignments</p>
        </div>
        <div class="header-buttons">
            <a href="#" onclick="location.reload();" class="badge text-black p-2 text-decoration-none fw-medium me-1" style="font-size:.875rem; border:1px solid rgb(229,229,229);">
                <i class="bi bi-arrow-clockwise me-3"></i>Refresh
            </a>
        </div>
    </div>

    <div class="bg-white border rounded p-4 mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-6">
                <input type="text" id="searchInput" class="form-control" placeholder="Search employees..." />
            </div>
            <div class="col-md-6 d-flex justify-content-end align-items-center gap-3">
                <input type="range" id="weekSlider" min="-2" max="10" value="0" style="width:200px;">
                <span id="weekLabel" class="fw-semibold"></span>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle text-center" id="scheduleTable">
            <thead class="table-light">
                <tr>
                    <th class="text-start align-middle"><i class="bi bi-people me-2"></i>Employee</th>
                    <!-- week headers dynamically added via JS -->
                </tr>
            </thead>
            <tbody id="employeesTableBody">
                <?php foreach ($employees as $userId => $employee): 
                    $fullName = htmlspecialchars($employee['full_name']);
                    $nameParts = explode(' ', trim($fullName));
                    $initials = '';
                    foreach ($nameParts as $part) { $initials .= strtoupper(substr($part,0,1)); }
                ?>
                <tr data-user-id="<?php echo $userId; ?>">
                    <td class="text-start employee-name">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;font-size:14px;font-weight:500;">
                                <?php echo $initials; ?>
                            </div>
                            <div>
                                <div class="fw-semibold"><?php echo $fullName; ?></div>
                                <div class="text-muted text-capitalize" style="font-size:12px;"><?php echo htmlspecialchars($employee['role']); ?></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const weekSlider = document.getElementById('weekSlider');
    const weekLabel = document.getElementById('weekLabel');
    const tableHeadRow = document.querySelector('#scheduleTable thead tr');
    const tableBody = document.getElementById('employeesTableBody');

    function fetchSchedule(offset){
        fetch(`ajax_schedule.php?week_offset=${offset}`)
        .then(res => res.json())
        .then(data => {
            // Update week label
            weekLabel.textContent = data.rangeLabel;

            // Update table headers
            tableHeadRow.innerHTML = `<th class="text-start align-middle"><i class="bi bi-people me-2"></i>Employee</th>`;
            data.mondays.forEach((weekStart, idx) => {
                const isCurrent = idx === data.currentWeekIndex;
                const th = document.createElement('th');
                th.className = isCurrent ? 'highlight-today' : '';
                th.innerHTML = `${weekStart.display}<br><small class="text-muted">Week of ${weekStart.short}</small>`;
                tableHeadRow.appendChild(th);
            });

            // Update table body cells
            tableBody.querySelectorAll('tr').forEach(tr => {
                const userId = tr.dataset.userId;
                tr.querySelectorAll('td:not(:first-child)').forEach(td => td.remove());

                data.mondays.forEach((weekStart, idx) => {
                    const td = document.createElement('td');
                    td.className = weekStart.hasTimeOff[userId] ? 'position-relative timeoff-cell' : '';
                    if(data.isAdmin){
                        td.style.cursor='pointer';
                    }
                    if(weekStart.entries[userId]){
                        weekStart.entries[userId].forEach(entry => {
                            const span = document.createElement('span');
                            span.className=`badge badge-status ${entry.statusClass} mt-1`;
                            span.textContent = `${entry.clientName} (${entry.assignedHours})`;
                            td.appendChild(span);
                            td.appendChild(document.createElement('br'));
                        });
                    } else {
                        td.innerHTML = '<span class="text-muted">+</span>';
                    }

                    // Time off hours
                    if(weekStart.timeOffHours && weekStart.timeOffHours[userId]){
                        const corner = document.createElement('span');
                        corner.className='timeoff-corner text-danger fw-semibold';
                        corner.textContent = weekStart.timeOffHours[userId];
                        td.appendChild(corner);
                    }

                    tr.appendChild(td);
                });
            });
        });
    }

    weekSlider.addEventListener('input', ()=>{
        fetchSchedule(weekSlider.value);
    });

    fetchSchedule(weekSlider.value);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
