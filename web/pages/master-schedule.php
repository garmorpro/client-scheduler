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

// Calculate Mondays as timestamps
$currentMonday = strtotime('monday this week', $today);
$weekOffset = isset($_GET['week_offset']) ? intval($_GET['week_offset']) : 0;
$startMonday = strtotime("-2 weeks", $currentMonday);
$startMonday = strtotime("+{$weekOffset} weeks", $startMonday);

$mondays = [];
for ($i = 0; $i < 7; $i++) {
    $mondays[] = strtotime("+{$i} weeks", $startMonday);
}

// Range label
$firstWeek = reset($mondays);
$lastWeek = end($mondays);
$rangeLabel = "Week of " . date('n/j', $firstWeek) . " - Week of " . date('n/j', $lastWeek);

// Get employees
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
        CASE 
            WHEN role = 'senior' THEN 1
            WHEN role = 'staff' THEN 2
        END,
        first_name ASC
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

// Fetch clients
$clientQuery = "SELECT engagement_id, client_name FROM engagements";
$clientResult = $conn->query($clientQuery);

if ($clientResult === false) {
    die('MySQL query failed: ' . $conn->error);
}

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
    FROM 
        entries a
    LEFT JOIN 
        engagements e ON a.engagement_id = e.engagement_id
    WHERE 
        a.week_start BETWEEN ? AND ?
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

$stmt->bind_param('ss', $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$entries = [];
while ($row = $result->fetch_assoc()) {
    $entries[$row['user_id']][$row['week_start']][] = [
        'entry_id' => $row['entry_id'],
        'client_name' => $row['client_name'],
        'assigned_hours' => $row['assigned_hours'],
        'engagement_id' => $row['engagement_id'],
        'engagement_status' => $row['engagement_status'],
        'is_timeoff' => (int)$row['is_timeoff'],
    ];
}

$stmt->close();

// Dropdown query
$dropdownquery = "
  SELECT 
    e.engagement_id,
    e.client_name,
    e.status,
    e.total_available_hours,
    IFNULL(SUM(a.assigned_hours), 0) AS assigned_hours
  FROM engagements e
  LEFT JOIN entries a ON a.engagement_id = e.engagement_id
  GROUP BY e.engagement_id
  ORDER BY e.client_name
";
$dropdownresult = $conn->query($dropdownquery);

$clientsWithHours = [];
while ($D_row = $dropdownresult->fetch_assoc()) {
  $clientsWithHours[] = $D_row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Master Schedule</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">

    <style>
      .timeoff-cell { background-color: rgb(217,217,217) !important; }
      <?php if ($isAdmin): ?>
      .timeoff-cell:hover { background-color: #e0f7fa !important; }
      <?php endif; ?>
      .timeoff-corner { position: absolute; top: 2px; right: 6px; font-size: .50rem; }
      .timeoff-card { border: 2px dashed rgb(209,226, 159) !important; background: rgb(246, 249, 236) !important; }

      .draggable-badge { cursor: grab; user-select: none; }
      .draggable-badge.dragging { opacity: 0.5; transform: scale(0.98); }
      td.drop-target { outline: 3px dashed rgba(0,123,255,0.15); }
      td.addable:hover { background: rgba(0,0,0,0.02); }
      .drop-indicator { display:inline-block; width:100%; height:6px; }
    </style>

    <script>
      const entries = <?php echo json_encode($entries); ?>;
      const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    </script>

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
            <a href="#" onclick="location.reload();" class="badge text-black p-2 text-decoration-none fw-medium me-1" style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
              <i class="bi bi-arrow-clockwise me-3"></i>Refresh
            </a>
        </div>
    </div>

    <div class="bg-white border rounded p-4 mb-4">
        <form id="filterForm" method="get" class="row g-3 align-items-center">
            <div class="col-md-6">
                <input type="text" id="searchInput" class="form-control" placeholder="Search employees..." onkeyup="filterEmployees()" />
            </div>
            <div class="col-md-6 d-flex justify-content-end align-items-center">
                <a href="?week_offset=<?php echo $weekOffset - 1; ?>" class="btn btn-outline-secondary btn-sm me-2" style="border-color: rgb(229,229,229);"><i class="bi bi-chevron-left"></i></a>
                <span class="fw-semibold"><?php echo $rangeLabel; ?></span>
                <a href="?week_offset=<?php echo $weekOffset + 1; ?>" class="btn btn-outline-secondary btn-sm ms-2" style="border-color: rgb(229,229,229);"><i class="bi bi-chevron-right"></i></a>
            </div>
        </form>
    </div>

    <?php
    $currentWeekIndex = null;
    foreach ($mondays as $idx => $monday) {
        $weekStart = $monday;
        $weekEnd = strtotime('+7 days', $weekStart);
        if ($today >= $weekStart && $today < $weekEnd) {
            $currentWeekIndex = $idx;
            break;
        }
    }
    ?>

    <div class="table-responsive">
        <table class="table table-bordered align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th class="text-start align-middle"><i class="bi bi-people me-2"></i>Employee</th>
                    <?php foreach ($mondays as $idx => $monday): ?>
                        <?php $weekStart = $monday; $isCurrent = ($idx === $currentWeekIndex); ?>
                        <th class="align-middle <?php echo $isCurrent ? 'highlight-today' : ''; ?>">
                            <?php echo date('M j', $weekStart); ?><br>
                            <small class="text-muted">Week of <?php echo date('n/j', $weekStart); ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <tbody id="employeesTableBody">
            <?php foreach ($employees as $userId => $employee): ?>
                <?php
                $fullName = htmlspecialchars($employee['full_name']);
                $nameParts = explode(' ', trim($fullName));
                $initials = '';
                foreach ($nameParts as $part) { $initials .= strtoupper(substr($part,0,1)); }
                $role = htmlspecialchars($employee['role']);
                ?>
                <tr>
                    <td class="text-start employee-name">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center me-3"
                                 style="width: 40px; height: 40px; font-size: 14px; font-weight: 500;">
                              <?php echo $initials; ?>
                            </div>
                            <div>
                              <div class="fw-semibold"><?php echo $fullName; ?></div>
                              <div class="text-muted text-capitalize" style="font-size: 12px;"><?php echo $role; ?></div>
                            </div>
                        </div>
                    </td>

                    <?php foreach ($mondays as $idx => $monday): ?>
                        <?php 
                        $weekStart = $monday;
                        $weekKey = date('Y-m-d', $weekStart);
                        $entriesForWeek = $entries[$userId][$weekKey] ?? [];
                        $hasTimeOff = false;
                        $timeOffHours = 0;
                        $cellContent = "";

                        if (!empty($entriesForWeek)) {
                            foreach ($entriesForWeek as $entry) {
                                if (!empty($entry['is_timeoff']) && intval($entry['is_timeoff']) === 1) {
                                    $hasTimeOff = true;
                                    $timeOffHours += floatval($entry['assigned_hours']);
                                }
                            }

                            foreach ($entriesForWeek as $entry) {
                                if (empty($entry['is_timeoff']) || intval($entry['is_timeoff']) !== 1) {
                                    $status = strtolower($entry['engagement_status'] ?? 'confirmed');
                                    switch ($status) {
                                        case 'confirmed': $entry_class = 'badge-confirmed'; break;
                                        case 'pending': $entry_class = 'badge-pending'; break;
                                        case 'not_confirmed': $entry_class = 'badge-not-confirmed'; break;
                                        default: $entry_class = 'badge-confirmed'; break;
                                    }
                                    $clientName = htmlspecialchars($entry['client_name']);
                                    $assignedHours = htmlspecialchars($entry['assigned_hours']);
                                    $draggableAttr = $isAdmin ? "draggable='true' class='badge badge-status $entry_class mt-1 draggable-badge'" : "class='badge badge-status $entry_class mt-1'";
                                    $badgeId = "badge-entry-{$entry['entry_id']}";
                                    $cellContent .= "<span id='{$badgeId}' {$draggableAttr} data-entry-id='{$entry['entry_id']}' data-user-id='{$userId}' data-week-start='{$weekKey}' title='Drag to move'>{$clientName} ({$assignedHours})</span><br>";
                                }
                            }
                        }

                        $tdClass = $hasTimeOff ? 'position-relative timeoff-cell' : '';
                        $placeholder = empty($cellContent) && $isAdmin ? "<span class='text-muted'>+</span>" : "";
                        ?>

                        <td class="<?php echo $isAdmin ? 'addable ' : ''; ?><?php echo $tdClass; ?>" 
                            data-user-id="<?php echo $userId; ?>" 
                            data-user-name="<?php echo htmlspecialchars($fullName); ?>"
                            data-week-start="<?php echo $weekKey; ?>" style="vertical-align: middle; cursor:pointer;">
                            <?php if ($hasTimeOff) echo "<span class='timeoff-corner text-danger fw-semibold'>{$timeOffHours}</span>"; ?>
                            <?php echo $cellContent; ?>
                            <?php echo $placeholder; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($isAdmin): ?>
        <?php include_once '../includes/modals/manage_entries_prompt.php'; ?>
        <?php include_once '../includes/modals/manage_entries.php'; ?>
        <?php include_once '../includes/modals/editEntryModal.php'; ?>
        <?php include_once '../includes/modals/add_entry.php'; ?>
        <?php include_once '../includes/modals/add_engagement.php'; ?>
    <?php endif; ?>

    <?php include_once '../includes/modals/engagement_details.php'; ?>
    <?php include_once '../includes/modals/user_details.php'; ?>
    <?php include_once '../includes/modals/viewProfileModal.php'; ?>
    <?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>

    <script src="../assets/js/open_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/add_entry_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/view_engagement_details.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/number_of_weeks.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/search.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/client_dropdown.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/dynamic_add_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/dynamic_manage_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/manage_entry_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/show_entries.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/edit_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/delete_entry.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/view_entry_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/view_user_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/filter_employees.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>

    <!-- Drag & Drop handler -->
    <script>
      (function () {
        if (!IS_ADMIN) return;
        document.addEventListener('DOMContentLoaded', function () {
          let draggedElem = null, draggedEntryId = null, originCell = null;

          function setupBadges() {
            document.querySelectorAll('.draggable-badge').forEach(badge => {
              badge.removeEventListener('dragstart', onDragStart);
              badge.removeEventListener('dragend', onDragEnd);
              badge.addEventListener('dragstart', onDragStart);
              badge.addEventListener('dragend', onDragEnd);
            });
          }

          function setupDropTargets() {
            document.querySelectorAll('td.addable').forEach(td => {
              td.removeEventListener('dragover', onDragOver);
              td.removeEventListener('dragenter', onDragEnter);
              td.removeEventListener('dragleave', onDragLeave);
              td.removeEventListener('drop', onDrop);
              td.addEventListener('dragover', onDragOver);
              td.addEventListener('dragenter', onDragEnter);
              td.addEventListener('dragleave', onDragLeave);
              td.addEventListener('drop', onDrop);
            });
          }

          function onDragStart(e) {
            draggedElem = this;
            draggedEntryId = this.dataset.entryId;
            originCell = this.closest('td');
            e.dataTransfer.setData('text/plain', draggedEntryId);
            try { e.dataTransfer.setDragImage(this, 10, 10); } catch {}
            this.classList.add('dragging');
          }

          function onDragEnd(e) {
            if (draggedElem) draggedElem.classList.remove('dragging');
            removeDropTargetHints();
            draggedElem = draggedEntryId = originCell = null;
          }

          function onDragOver(e) { e.preventDefault(); this.classList.add('drop-target'); e.dataTransfer.dropEffect='move'; }
          function onDragEnter(e) { e.preventDefault(); this.classList.add('drop-target'); }
          function onDragLeave(e) { this.classList.remove('drop-target'); }
          function removeDropTargetHints() { document.querySelectorAll('td.addable.drop-target').forEach(td=>td.classList.remove('drop-target')); }

          async function onDrop(e) {
            e.preventDefault(); removeDropTargetHints();
            const targetTd = this;
            const targetUserId = targetTd.dataset.userId;
            const targetWeekStart = targetTd.dataset.weekStart;
            const entryId = e.dataTransfer.getData('text/plain');
            if (!entryId) return;
            if (originCell && originCell.dataset.userId === targetUserId && originCell.dataset.weekStart === targetWeekStart) return;

            let badge = document.querySelector('#badge-entry-'+entryId);
            if (!badge) { alert('Badge not found'); return; }

            badge.style.pointerEvents='none'; badge.classList.add('dragging');
            const loadingDot = document.createElement('span'); loadingDot.className='ms-1 text-muted'; loadingDot.innerText='...';
            targetTd.appendChild(loadingDot);

            try {
              const resp = await fetch('update_entry_position.php', {
                method:'POST', credentials:'same-origin',
                headers:{'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({ entry_id: entryId, target_user_id: targetUserId, target_week_start: targetWeekStart })
              });
              const data = await resp.json();
              if (!resp.ok || !data.success) throw new Error(data.error||'Server error');

              // remove '+' placeholder if exists
              const placeholder = targetTd.querySelector('.text-muted');
              if (placeholder && targetTd.querySelectorAll('span, .badge').length === 1) placeholder.remove();

              targetTd.appendChild(badge); targetTd.appendChild(document.createElement('br'));
              badge.dataset.userId = targetUserId; badge.dataset.weekStart = targetWeekStart;
              badge.style.pointerEvents=''; badge.classList.remove('dragging');
              loadingDot.remove();

            } catch(err) {
              console.error(err); alert('Failed to move entry');
              badge.style.pointerEvents=''; badge.classList.remove('dragging'); loadingDot.remove();
            }
          }

          setupBadges(); setupDropTargets();
          const tableObserver = new MutationObserver(()=>{ setupBadges(); setupDropTargets(); });
          const table = document.querySelector('.table-responsive');
          if(table) tableObserver.observe(table,{ childList:true,subtree:true });
        });
      })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div>
</body>
</html>
