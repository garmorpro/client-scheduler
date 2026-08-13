<?php
// Checks audit_engagement_timeline / audit_engagement_milestones for items
// due in 1-7 days (timeline) or 1-5 days (milestones, matching Engagement
// Tracker's own window) - not completed, not already notified - and emails
// everyone staffed on that engagement (via entries, Phase 0's team-
// membership rule). Ported from Engagement Tracker's notification-helper.php
// (checkUpcomingKeyDates/checkUpcomingMilestones), but re-targeted: ET
// broadcast one message per engagement to a single shared Slack channel/
// ntfy topic; email is a per-recipient channel, and Garrett wants this
// actually targeted at each engagement's real team rather than one shared
// inbox, so this groups everything by RECIPIENT and sends one combined
// digest email per person covering every engagement they're staffed on -
// nobody wants N separate emails for being on N engagements with items due
// the same week.
//
// "Ready to archive" (ET's third check) isn't ported as its own concept -
// Client Scheduler's engagements.status doesn't have an archive lifecycle
// state the way Engagement Tracker's eng_status did, and the Archive
// timeline date is already covered by the normal due-date check below.

require_once __DIR__ . '/email_functions.php';

function getAuditTimelineDateFields(): array
{
    return [
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
}

function getAuditRangeStartFields(): array
{
    return [
        'fieldwork_client_calls_end_date' => 'fieldwork_client_calls_start_date',
        'fieldwork_documentation_end_date' => 'fieldwork_documentation_start_date',
    ];
}

function auditAlreadyNotified(mysqli $conn, int $engagementId, string $field): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM audit_notification_log WHERE engagement_id = ? AND notif_field = ? LIMIT 1");
    $stmt->bind_param('is', $engagementId, $field);
    $stmt->execute();
    $exists = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    return $exists;
}

function auditMarkNotified(mysqli $conn, int $engagementId, string $field): void
{
    $stmt = $conn->prepare("INSERT IGNORE INTO audit_notification_log (engagement_id, notif_field) VALUES (?, ?)");
    $stmt->bind_param('is', $engagementId, $field);
    $stmt->execute();
    $stmt->close();
}

/** Everyone staffed on this engagement (an entries row), restricted to roles that'd act on an audit due date. */
function getAuditEngagementRecipients(mysqli $conn, int $engagementId): array
{
    $stmt = $conn->prepare("
        SELECT DISTINCT u.user_id, u.full_name, u.email
        FROM entries en
        JOIN users u ON u.user_id = en.user_id
        WHERE en.engagement_id = ? AND u.role IN ('manager', 'senior', 'staff', 'intern')
          AND u.email IS NOT NULL AND u.email <> ''
    ");
    $stmt->bind_param('i', $engagementId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function auditFormatKeyDate(string $dateValue): string
{
    return date('M j, Y', strtotime($dateValue));
}
function auditFormatDaysAway(int $days): string
{
    if ($days === 0) return 'today';
    if ($days === 1) return 'tomorrow';
    return "in {$days} days";
}

// Calendar-day difference between today and $dateValue, ignoring
// time-of-day entirely - comparing raw timestamps (today's exact current
// moment vs. midnight of the due date) understates the gap by however far
// into the current day it already is, so "due tomorrow" would round down
// to 0 (and get skipped, since the check requires >= 1) any time this ran
// after roughly midday. A due date is a calendar day, not an exact moment.
function auditDaysUntil(string $dateValue): int
{
    $todayMidnight = strtotime('today');
    $dueMidnight = strtotime(date('Y-m-d', strtotime($dateValue)));
    return (int) round(($dueMidnight - $todayMidnight) / 86400);
}

/**
 * Scans every engagement's timeline for items due in 1-7 days. Returns a
 * flat list of per-(recipient, item) rows to be grouped into per-recipient
 * digests by the caller - doesn't send anything itself. Marks each
 * qualifying field as notified as it's found, same "notify once" contract
 * as the source - unless $dryRun, which skips that so repeated test runs
 * stay idempotent instead of consuming the dedup log.
 */
function collectUpcomingKeyDateDigestRows(mysqli $conn, bool $dryRun = false): array
{
    $dateFields = getAuditTimelineDateFields();
    $rangeStartFields = getAuditRangeStartFields();
    $rows = [];

    $res = $conn->query("
        SELECT t.*, e.client_name
        FROM audit_engagement_timeline t
        JOIN engagements e ON t.engagement_id = e.engagement_id
    ");
    while ($timeline = $res->fetch_assoc()) {
        $engagementId = (int) $timeline['engagement_id'];
        $recipients = null; // lazy-loaded only if something's actually due

        foreach ($dateFields as $dateCol => [$completedCol, $title]) {
            $dateValue = $timeline[$dateCol] ?? null;
            $completedValue = $timeline[$completedCol] ?? null;
            if (!$dateValue || $completedValue) continue;

            $daysUntil = auditDaysUntil($dateValue);
            if ($daysUntil < 1 || $daysUntil > 7) continue;
            if (auditAlreadyNotified($conn, $engagementId, $dateCol)) continue;

            $dateLabel = auditFormatKeyDate($dateValue);
            $startCol = $rangeStartFields[$dateCol] ?? null;
            if ($startCol && !empty($timeline[$startCol])) {
                $dateLabel = auditFormatKeyDate($timeline[$startCol]) . ' - ' . $dateLabel;
            }

            if ($recipients === null) {
                $recipients = getAuditEngagementRecipients($conn, $engagementId);
            }
            foreach ($recipients as $person) {
                $rows[] = [
                    'user_id' => $person['user_id'],
                    'full_name' => $person['full_name'],
                    'email' => $person['email'],
                    'client_name' => $timeline['client_name'],
                    'title' => $title,
                    'date_label' => $dateLabel,
                    'days_away' => auditFormatDaysAway($daysUntil),
                ];
            }
            if (!$dryRun) {
                auditMarkNotified($conn, $engagementId, $dateCol);
            }
        }
    }
    return $rows;
}

/** Same idea as above, for milestones - matches ET's 5-day (not 7-day) window. */
function collectUpcomingMilestoneDigestRows(mysqli $conn, bool $dryRun = false): array
{
    $rows = [];
    $res = $conn->query("
        SELECT m.milestone_id, m.milestone_type, m.due_date, m.is_completed, m.engagement_id, e.client_name
        FROM audit_engagement_milestones m
        JOIN engagements e ON m.engagement_id = e.engagement_id
        WHERE m.due_date IS NOT NULL AND m.is_completed = 0
    ");
    while ($row = $res->fetch_assoc()) {
        $daysUntil = auditDaysUntil($row['due_date']);
        if ($daysUntil < 1 || $daysUntil > 5) continue;

        $engagementId = (int) $row['engagement_id'];
        $field = 'milestone_' . $row['milestone_id'];
        if (auditAlreadyNotified($conn, $engagementId, $field)) continue;

        $recipients = getAuditEngagementRecipients($conn, $engagementId);
        $title = implode(' ', array_map('ucfirst', explode('_', strtolower($row['milestone_type']))));
        foreach ($recipients as $person) {
            $rows[] = [
                'user_id' => $person['user_id'],
                'full_name' => $person['full_name'],
                'email' => $person['email'],
                'client_name' => $row['client_name'],
                'title' => $title,
                'date_label' => auditFormatKeyDate($row['due_date']),
                'days_away' => auditFormatDaysAway($daysUntil),
            ];
        }
        if (!$dryRun) {
            auditMarkNotified($conn, $engagementId, $field);
        }
    }
    return $rows;
}

/**
 * Groups digest rows by recipient and sends one combined email per person.
 * Returns one result row per recipient: ['name', 'email', 'items', 'sent']
 * - 'sent' is always false in dry-run mode (nothing is actually emailed,
 * and the dedup log is left untouched so a real run afterward still sees
 * everything). sendEmail() itself also no-ops and returns false if email
 * notifications are disabled in Settings, independent of dry-run.
 */
function sendAuditDueDateDigests(mysqli $conn, bool $dryRun = false): array
{
    $rows = array_merge(
        collectUpcomingKeyDateDigestRows($conn, $dryRun),
        collectUpcomingMilestoneDigestRows($conn, $dryRun)
    );
    if (empty($rows)) return [];

    $byRecipient = [];
    foreach ($rows as $row) {
        $byRecipient[$row['user_id']]['name'] = $row['full_name'];
        $byRecipient[$row['user_id']]['email'] = $row['email'];
        $byRecipient[$row['user_id']]['items'][] = $row;
    }

    $results = [];
    foreach ($byRecipient as $recipient) {
        $count = count($recipient['items']);
        $lastIdx = $count - 1;
        // Table-based rows with inline styles, not <ul>/<li> - matches
        // the branded shell's email-safe conventions (wrapBrandedEmailHtml()
        // in email_functions.php, which this content gets wrapped in).
        $rowsHtml = '';
        foreach ($recipient['items'] as $idx => $item) {
            $border = $idx === $lastIdx ? '' : 'border-bottom:1px solid #e3e7e5;';
            $rowsHtml .= '<tr><td style="padding:14px 0; ' . $border . '">'
                . '<div style="font-size:13px; font-weight:700; color:#003f47;">' . htmlspecialchars($item['client_name']) . '</div>'
                . '<div style="font-size:13.5px; color:#16211f; margin-top:3px;">' . htmlspecialchars($item['title']) . ' &mdash; due ' . htmlspecialchars($item['date_label'])
                . ' <span style="color:#6b7570; font-size:12.5px;">(' . htmlspecialchars($item['days_away']) . ')</span></div>'
                . '</td></tr>';
        }

        $subject = $count === 1 ? 'Upcoming Due Date' : "{$count} Upcoming Due Dates";
        $body = '<p style="margin:0 0 4px; font-size:15px;">Hi ' . htmlspecialchars($recipient['name']) . ',</p>'
              . '<p style="margin:0 0 18px; color:#6b7570;">You have ' . $count . ' upcoming ' . ($count === 1 ? 'item' : 'items') . ' due soon:</p>'
              . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $rowsHtml . '</table>';

        $sent = $dryRun ? false : sendEmail($recipient['email'], $subject, $body, $conn);

        $results[] = [
            'name' => $recipient['name'],
            'email' => $recipient['email'],
            'items' => $recipient['items'],
            'sent' => $sent,
        ];
    }
    return $results;
}
