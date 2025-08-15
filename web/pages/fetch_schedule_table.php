<?php
require_once '../includes/db.php';
session_start();

// Only allow admins to drag badges
$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';

// ------------------------------
// Fetch users
// ------------------------------
$users = [];
$userResult = $conn->query("SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name, role FROM users ORDER BY user_id");
if ($userResult) {
    while ($user = $userResult->fetch_assoc()) {
        $users[$user['user_id']] = $user;
    }
}

// ------------------------------
// Define weeks to display (next 4 weeks)
// ------------------------------
$mondays = [];
$today = strtotime('today');
$startDate = strtotime('monday this week');
for ($i = 0; $i < 4; $i++) {
    $mondays[] = strtotime("+$i week", $startDate);
}

// ------------------------------
// Fetch all entries
// ------------------------------
$entries = []; // user_id => week_start => array of entries

$entryQuery = "
    SELECT e.entry_id, e.user_id, e.week_start, e.assigned_hours, e.is_timeoff, e.engagement_status, c.client_name
    FROM entries e
    LEFT JOIN clients c ON e.client_id = c.client_id
    ORDER BY e.user_id, e.week_start
";
$result = $conn->query($entryQuery);
if ($result) {
    while ($entry = $result->fetch_assoc()) {
        $weekKey = date('Y-m-d', strtotime($entry['week_start']));
        $entries[$entry['user_id']][$weekKey][] = $entry;
    }
}

// ------------------------------
// Find current week index
// ------------------------------
$currentWeekIndex = null;
foreach ($mondays as $idx => $monday) {
    $weekStart = $monday;
    $weekEnd = strtotime('+7 days', $weekStart);
    if ($today >= $weekStart && $today < $weekEnd) {
        $currentWeekIndex = $idx;
        break;
    }
}

// ------------------------------
// Output table
// ------------------------------
?>
<div class="table-responsive" style="overflow-x: auto;">
    <table class="table table-bordered align-middle text-center">
        <thead class="table-light">
            <tr>
                <th class="text-start align-middle"><i class="bi bi-people me-2"></i>Employee</th>
                <?php foreach ($mondays as $idx => $monday): 
                    $isCurrent = ($idx === $currentWeekIndex);
                    ?>
                    <th class="align-middle week <?php echo $isCurrent ? 'highlight-today' : ''; ?>">
                        <?php echo date('M j', $monday); ?><br>
                        <small class="text-muted">Week of <?php echo date('n/j', $monday); ?></small>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody id="employeesTableBody">
            <?php foreach ($users as $userId => $employee): 
                $fullName = htmlspecialchars($employee['full_name']);
                $nameParts = explode(' ', trim($fullName));
                $initials = '';
                foreach ($nameParts as $part) $initials .= strtoupper(substr($part, 0, 1));
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

                <?php foreach ($mondays as $idx => $monday):
                    $isCurrent = ($idx === $currentWeekIndex);
                    $weekKey = date('Y-m-d', $monday);
                    $entriesForWeek = $entries[$userId][$weekKey] ?? [];
                    $cellContent = "";
                    $hasTimeOff = false;
                    $timeOffHours = 0;

                    if (!empty($entriesForWeek)) {
                        foreach ($entriesForWeek as $entry) {
                            if (!empty($entry['is_timeoff']) && intval($entry['is_timeoff']) === 1) {
                                $hasTimeOff = true;
                                $timeOffHours += floatval($entry['assigned_hours']);
                            }
                        }

                        foreach ($entriesForWeek as $entry) {
                            if (empty($entry['is_timeoff']) || intval($entry['is_timeoff']) !== 1) {
                                $engagementStatus = strtolower($entry['engagement_status'] ?? 'confirmed');
                                switch ($engagementStatus) {
                                    case 'confirmed': $entry_class = 'badge-confirmed'; break;
                                    case 'pending': $entry_class = 'badge-pending'; break;
                                    case 'not_confirmed': $entry_class = 'badge-not-confirmed'; break;
                                    default: $entry_class = 'badge-confirmed'; break;
                                }
                                $clientName = htmlspecialchars($entry['client_name']);
                                $assignedHours = htmlspecialchars($entry['assigned_hours']);
                                $draggableAttr = $isAdmin ? "draggable='true' class='badge badge-status $entry_class mt-1 draggable-badge' " : "class='badge badge-status $entry_class mt-1' ";
                                $badgeId = "badge-entry-{$entry['entry_id']}";
                                $cellContent .= "<span id='{$badgeId}' {$draggableAttr} data-entry-id='{$entry['entry_id']}' data-user-id='{$userId}' data-week-start='{$weekKey}' title='Drag to move'>{$clientName} ({$assignedHours})</span><br>";
                            }
                        }
                    } else {
                        $cellContent = $isAdmin ? "<i class='bi bi-plus text-muted'></i>" : "";
                    }

                    $tdClass = ($isCurrent ? '' : '');
                    if ($hasTimeOff) $tdClass .= ' position-relative timeoff-cell';
                ?>
                    <?php if ($isAdmin): ?>
                        <td class="addable <?php echo $tdClass; ?>" 
                            style="cursor:pointer; vertical-align: middle;"
                            data-user-id="<?php echo $userId; ?>" 
                            data-user-name="<?php echo $fullName; ?>"
                            data-week-start="<?php echo $weekKey; ?>">

                            <?php 
                              if ($hasTimeOff) echo "<span class='timeoff-corner text-danger fw-semibold'>{$timeOffHours}</span>";
                              echo $cellContent; 
                            ?>
                        </td>
                    <?php else: ?>
                        <td class="<?php echo $tdClass; ?>">
                            <?php 
                              if ($hasTimeOff) echo "<span class='timeoff-corner text-danger'>{$timeOffHours}</span>";
                              echo $cellContent; 
                            ?>
                        </td>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
