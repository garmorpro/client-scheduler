<?php
require_once '../includes/db.php';
session_start();

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isManager = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'manager';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$today = strtotime('today');
$currentMonday = strtotime('monday this week', $today);
$weekOffset = isset($_GET['week_offset']) ? intval($_GET['week_offset']) : 0;
$startMonday = strtotime("-2 weeks", $currentMonday);
$startMonday = strtotime("+{$weekOffset} weeks", $startMonday);

$mondays = [];
for ($i = 0; $i < 7; $i++) {
    $mondays[] = strtotime("+{$i} weeks", $startMonday);
}

$firstWeek = reset($mondays);
$lastWeek = end($mondays);
$rangeLabel = "Week of " . date('n/j', $firstWeek) . " - Week of " . date('n/j', $lastWeek);

$employees = [];
$userQuery = "
    SELECT 
        user_id, 
        CONCAT(first_name, ' ', last_name) AS full_name, 
        role 
    FROM users 
    WHERE status = 'active' 
      AND role IN ('staff', 'senior')
    ORDER BY 
        CASE WHEN role = 'senior' THEN 1
             WHEN role = 'staff' THEN 2
        END,
        first_name ASC
";
$userResult = $conn->query($userQuery);
while ($userRow = $userResult->fetch_assoc()) {
    $employees[$userRow['user_id']] = [
        'full_name' => $userRow['full_name'],
        'role' => $userRow['role']
    ];
}

$clientQuery = "SELECT engagement_id, client_name FROM engagements";
$clientResult = $conn->query($clientQuery);
$activeClients = [];
while ($clientRow = $clientResult->fetch_assoc()) {
    $activeClients[] = $clientRow;
}

$startDate = date('Y-m-d', $startMonday);
$endDate = date('Y-m-d', strtotime('+6 weeks', $startMonday));

$query = "
    SELECT 
        a.entry_id,
        a.user_id,
        a.engagement_id,
        e.client_name,
        a.week_start,
        a.assigned_hours,
        e.status AS engagement_status,
        a.is_timeoff
    FROM entries a
    LEFT JOIN engagements e ON a.engagement_id = e.engagement_id
    WHERE a.week_start BETWEEN ? AND ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$entries = [];
while ($row = $result->fetch_assoc()) {
    $entries[$row['user_id']][$row['week_start']][] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Master Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        .timeoff-cell { background-color: rgb(217,217,217) !important; }
        .timeoff-card { border: 2px dashed rgb(209,226, 159) !important; background: rgb(246, 249, 236) !important; }
        .drag-over { outline: 2px dashed #007bff; background: #eef6ff; }
        .badge-status { cursor: grab; }
    </style>
</head>
<body class="d-flex">
<?php include_once '../templates/sidebar.php'; ?>
<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <h3>Master Schedule</h3>
    <div class="table-responsive">
        <table class="table table-bordered align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th class="text-start">Employee</th>
                    <?php foreach ($mondays as $monday): ?>
                        <th><?php echo date('M j', $monday); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($employees as $userId => $employee): ?>
                <tr>
                    <td class="text-start"><?php echo htmlspecialchars($employee['full_name']); ?></td>
                    <?php foreach ($mondays as $monday): ?>
                        <?php
                        $weekKey = date('Y-m-d', $monday);
                        $entriesForWeek = $entries[$userId][$weekKey] ?? [];
                        ?>
                        <td class="drop-cell <?php echo $isAdmin ? 'addable' : ''; ?>" 
                            data-user-id="<?php echo $userId; ?>" 
                            data-week-start="<?php echo $weekKey; ?>">
                            <?php foreach ($entriesForWeek as $entry): ?>
                                <?php
                                if (intval($entry['is_timeoff']) === 1) continue;
                                $statusClass = 'badge-confirmed';
                                if (strtolower($entry['engagement_status']) === 'pending') $statusClass = 'badge-pending';
                                elseif (strtolower($entry['engagement_status']) === 'not_confirmed') $statusClass = 'badge-not-confirmed';
                                ?>
                                <span class="badge badge-status <?php echo $statusClass; ?> mt-1"
                                    draggable="true"
                                    data-entry-id="<?php echo $entry['entry_id']; ?>">
                                    <?php echo htmlspecialchars($entry['client_name']); ?> (<?php echo htmlspecialchars($entry['assigned_hours']); ?>)
                                </span><br>
                            <?php endforeach; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Drag and drop handling
let draggedBadge = null;

document.addEventListener('dragstart', function(e) {
    if (e.target.classList.contains('badge-status')) {
        draggedBadge = e.target;
        e.dataTransfer.effectAllowed = 'move';
    }
});

document.addEventListener('dragover', function(e) {
    if (e.target.closest('.drop-cell')) {
        e.preventDefault();
        e.target.closest('.drop-cell').classList.add('drag-over');
    }
});

document.addEventListener('dragleave', function(e) {
    if (e.target.closest('.drop-cell')) {
        e.target.closest('.drop-cell').classList.remove('drag-over');
    }
});

document.addEventListener('drop', function(e) {
    const dropCell = e.target.closest('.drop-cell');
    if (dropCell && draggedBadge) {
        e.preventDefault();
        dropCell.classList.remove('drag-over');

        const entryId = draggedBadge.dataset.entryId;
        const newUserId = dropCell.dataset.userId;
        const newWeekStart = dropCell.dataset.weekStart;

        // Move visually
        dropCell.appendChild(draggedBadge);
        draggedBadge = null;

        // Update in DB
        fetch('update_entry_week.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `entry_id=${entryId}&user_id=${newUserId}&week_start=${newWeekStart}`
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert('Update failed: ' + data.message);
            }
        })
        .catch(err => alert('Error updating entry: ' + err));
    }
});
</script>
</body>
</html>
