<?php
// Ported from Engagement Tracker's getCalendarItemsForMonth() (includes/functions.php)
// + api/get-calendar-items.php, re-pointed at audit_engagement_timeline /
// audit_engagement_milestones (keyed by engagement_id, not engagement_idno)
// and Client Scheduler's engagements.client_name instead of eng_name.
// Deliberately NOT scoped to the logged-in user, unlike the DOL Generator -
// this is a shared team calendar (every key date across every engagement),
// not a personal work queue, so restricting it to "engagements I'm staffed
// on" would defeat the point.
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/avatar_helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'view_audit_timeline')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$year = (int) ($_GET['year'] ?? date('Y'));
$month = (int) ($_GET['month'] ?? date('n'));

if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid year/month']);
    exit();
}

$monthStart = sprintf('%04d-%02d-01', $year, $month);
$monthEnd = date('Y-m-t', strtotime($monthStart));

$dateFields = [
    'internal_planning_call_date' => ['internal_planning_call_completed_at', 'Internal Planning Call'],
    'planning_memo_date' => ['planning_memo_completed_at', 'Planning Memo'],
    'irl_due_date' => ['irl_completed_at', 'IRL Due Date'],
    'client_planning_call_date' => ['client_planning_call_completed_at', 'Client Planning Call'],
    'fieldwork_client_calls_end_date' => ['fieldwork_client_calls_completed_at', 'Fieldwork - Client Calls'],
    'fieldwork_documentation_end_date' => ['fieldwork_documentation_completed_at', 'Fieldwork - Documentation'],
    'leadsheet_date' => ['leadsheet_completed_at', 'Leadsheet'],
    'conclusion_memo_date' => ['conclusion_memo_completed_at', 'Conclusion Memo'],
    'draft_report_due_date' => ['draft_report_completed_at', 'Draft Report Due'],
    'final_report_date' => ['final_report_completed_at', 'Final Report'],
    'archive_date' => ['archive_completed_at', 'Archive'],
];
// end-date field -> paired start-date field - these render as a spanning
// bar across the whole range on the calendar, not just a dot on the end date.
$rangeStartFields = [
    'fieldwork_client_calls_end_date' => 'fieldwork_client_calls_start_date',
    'fieldwork_documentation_end_date' => 'fieldwork_documentation_start_date',
];

$items = [];

$tlQuery = "
    SELECT t.*, e.client_name
    FROM audit_engagement_timeline t
    JOIN engagements e ON t.engagement_id = e.engagement_id
";
$tlResult = $conn->query($tlQuery);
if ($tlResult) {
    while ($timeline = $tlResult->fetch_assoc()) {
        $avatarColor = avatar_color($timeline['client_name']);
        $initials = avatar_initials($timeline['client_name']);

        foreach ($dateFields as $dateCol => [$completedCol, $title]) {
            $dateValue = $timeline[$dateCol] ?? null;
            if (!$dateValue) continue;

            $startCol = $rangeStartFields[$dateCol] ?? null;
            $startValue = ($startCol && !empty($timeline[$startCol])) ? $timeline[$startCol] : null;

            // Overlap test against the requested month, not just "is the end
            // date in this month" - a range that starts last month and ends
            // this month (or vice versa) still needs to show up.
            $rangeStart = $startValue ?? $dateValue;
            if ($dateValue < $monthStart || $rangeStart > $monthEnd) continue;

            $items[] = [
                'engagement_id' => (int) $timeline['engagement_id'],
                'client_name' => $timeline['client_name'],
                'avatar_color' => $avatarColor,
                'initials' => $initials,
                'title' => $title,
                'date' => $dateValue,
                'start_date' => $startValue,
                'completed' => !empty($timeline[$completedCol]),
                'type' => 'key_date',
            ];
        }

        // Weekly status call: recurs every week on the chosen weekday -
        // expand every occurrence that falls in the requested month.
        if ($timeline['weekly_status_call_day'] !== null && $timeline['weekly_status_call_day'] !== '') {
            $targetDow = (int) $timeline['weekly_status_call_day'];
            $cursor = new DateTime($monthStart);
            $daysToAdd = ($targetDow - (int) $cursor->format('w') + 7) % 7;
            $cursor->modify("+{$daysToAdd} days");
            while ($cursor->format('Y-m-d') <= $monthEnd) {
                $items[] = [
                    'engagement_id' => (int) $timeline['engagement_id'],
                    'client_name' => $timeline['client_name'],
                    'avatar_color' => $avatarColor,
                    'initials' => $initials,
                    'title' => 'Weekly Status Call',
                    'date' => $cursor->format('Y-m-d'),
                    'start_date' => null,
                    'completed' => false,
                    'type' => 'weekly_call',
                    // Shared across every engagement linked to the same call,
                    // null if not linked - lets the calendar combine linked
                    // engagements into one entry instead of duplicate chips.
                    'call_group' => $timeline['weekly_status_call_group'] ?? null,
                    'call_group_name' => $timeline['weekly_status_call_group_name'] ?? null,
                ];
                $cursor->modify('+7 days');
            }
        }
    }
}

$msQuery = "
    SELECT m.milestone_type, m.due_date, m.is_completed, m.engagement_id, e.client_name
    FROM audit_engagement_milestones m
    JOIN engagements e ON m.engagement_id = e.engagement_id
    WHERE m.due_date IS NOT NULL
";
$msResult = $conn->query($msQuery);
if ($msResult) {
    while ($row = $msResult->fetch_assoc()) {
        $dateValue = $row['due_date'];
        if (!$dateValue || $dateValue < $monthStart || $dateValue > $monthEnd) continue;

        $items[] = [
            'engagement_id' => (int) $row['engagement_id'],
            'client_name' => $row['client_name'],
            'avatar_color' => avatar_color($row['client_name']),
            'initials' => avatar_initials($row['client_name']),
            'title' => implode(' ', array_map('ucfirst', explode('_', strtolower($row['milestone_type'])))),
            'date' => $dateValue,
            'start_date' => null,
            'completed' => (int) $row['is_completed'] === 1,
            'type' => 'milestone',
        ];
    }
}

usort($items, fn($a, $b) => $a['date'] <=> $b['date']);

echo json_encode(['success' => true, 'year' => $year, 'month' => $month, 'items' => $items]);
