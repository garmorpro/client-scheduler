<?php
require_once '../includes/db.php';
session_start();

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';

$userId = intval($_GET['user_id']);
if (!$userId) exit;

$today = strtotime('today');
$currentMonday = strtotime('monday this week', $today);
$weekOffset = isset($_GET['week_offset']) ? intval($_GET['week_offset']) : 0;
$startMonday = strtotime("-2 weeks", $currentMonday);
$startMonday = strtotime("+{$weekOffset} weeks", $startMonday);

$mondays = [];
for ($i = 0; $i < 7; $i++) {
    $mondays[] = strtotime("+{$i} weeks", $startMonday);
}

// Fetch employee
$userQuery = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name, role FROM users WHERE user_id=?");
$userQuery->bind_param('i', $userId);
$userQuery->execute();
$userResult = $userQuery->get_result();
$employee = $userResult->fetch_assoc();
$userQuery->close();

// Fetch entries for this user
$startDate = date('Y-m-d', reset($mondays));
$endDate = date('Y-m-d', strtotime('+6 weeks', reset($mondays)));

$stmt = $conn->prepare("
    SELECT a.entry_id, a.engagement_id, e.client_name, a.week_start, a.assigned_hours, e.status AS engagement_status, a.is_timeoff
    FROM entries a
    LEFT JOIN engagements e ON a.engagement_id = e.engagement_id
    WHERE a.user_id=? AND a.week_start BETWEEN ? AND ?
");
$stmt->bind_param('iss', $userId, $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$entries = [];
while ($row = $result->fetch_assoc()) {
    $entries[$row['week_start']][] = [
        'entry_id' => $row['entry_id'],
        'client_name' => $row['client_name'],
        'assigned_hours' => $row['assigned_hours'],
        'engagement_id' => $row['engagement_id'],
        'engagement_status' => $row['engagement_status'],
        'is_timeoff' => (int)$row['is_timeoff']
    ];
}
$stmt->close();

// Build <tr> for this employee
$fullName = htmlspecialchars($employee['full_name']);
$role = htmlspecialchars($employee['role']);
$nameParts = explode(' ', trim($fullName));
$initials = '';
foreach ($nameParts as $part) $initials .= strtoupper(substr($part, 0, 1));

echo "<tr data-user-id='{$userId}'>";
echo "<td class='text-start employee-name'>
        <div class='d-flex align-items-center'>
            <div class='rounded-circle bg-dark text-white d-flex align-items-center justify-content-center me-3'
                style='width: 40px; height: 40px; font-size: 14px; font-weight: 500;'>{$initials}</div>
            <div>
                <div class='fw-semibold'>{$fullName}</div>
                <div class='text-muted text-capitalize' style='font-size: 12px;'>{$role}</div>
            </div>
        </div>
      </td>";

foreach ($mondays as $monday) {
    $weekKey = date('Y-m-d', $monday);
    $entriesForWeek = $entries[$weekKey] ?? [];
    $cellContent = '';
    $hasTimeOff = false;
    $timeOffHours = 0;

    if (!empty($entriesForWeek)) {
        foreach ($entriesForWeek as $entry) {
            if (!empty($entry['is_timeoff']) && $entry['is_timeoff'] == 1) {
                $hasTimeOff = true;
                $timeOffHours += $entry['assigned_hours'];
            }
        }

        foreach ($entriesForWeek as $entry) {
            if (empty($entry['is_timeoff']) || $entry['is_timeoff'] != 1) {
                $status = strtolower($entry['engagement_status'] ?? 'confirmed');
                switch ($status) {
                    case 'confirmed': $entry_class='badge-confirmed'; break;
                    case 'pending': $entry_class='badge-pending'; break;
                    case 'not_confirmed': $entry_class='badge-not-confirmed'; break;
                    default: $entry_class='badge-confirmed'; break;
                }
                $badgeId = "badge-entry-{$entry['entry_id']}";
                $draggableAttr = $isAdmin ? "draggable='true' class='badge badge-status $entry_class mt-1 draggable-badge'" : "class='badge badge-status $entry_class mt-1'";
                $clientName = htmlspecialchars($entry['client_name']);
                $assignedHours = htmlspecialchars($entry['assigned_hours']);
                $cellContent .= "<span id='{$badgeId}' {$draggableAttr} data-entry-id='{$entry['entry_id']}' data-user-id='{$userId}' data-week-start='{$weekKey}' title='Drag to move'>{$clientName} ({$assignedHours})</span><br>";
            }
        }
    } else {
        $cellContent = "<span class='text-muted'>" . ($isAdmin ? "+" : "") . "</span>";
    }

    $tdClass = '';
    if ($hasTimeOff) $tdClass .= ' position-relative timeoff-cell';

    if ($isAdmin) {
        echo "<td class='addable {$tdClass}' data-user-id='{$userId}' data-week-start='{$weekKey}' style='cursor:pointer; vertical-align: middle;'>";
        if ($hasTimeOff) echo "<span class='timeoff-corner text-danger fw-semibold'>{$timeOffHours}</span>";
        echo $cellContent;
        echo "</td>";
    } else {
        echo "<td class='{$tdClass}'>";
        if ($hasTimeOff) echo "<span class='timeoff-corner text-danger fw-semibold'>{$timeOffHours}</span>";
        echo $cellContent;
        echo "</td>";
    }
}
echo "</tr>";
