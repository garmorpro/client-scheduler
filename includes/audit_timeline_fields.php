<?php
// Shared whitelist of audit_engagement_timeline's editable columns, plus the
// per-engagement authorization check used by every audit-timeline write
// endpoint. Centralized here so update_audit_timeline_field.php and
// update_audit_milestone.php can't drift from what engagement-details.php
// actually reads, and so no endpoint ever interpolates a raw column name
// straight from the request.

// column => 'date' | 'completed'
function audit_timeline_field_map() {
    return [
        'internal_planning_call_date' => 'date',
        'internal_planning_call_completed_at' => 'completed',
        'planning_memo_date' => 'date',
        'planning_memo_completed_at' => 'completed',
        'irl_due_date' => 'date',
        'irl_completed_at' => 'completed',
        'client_planning_call_date' => 'date',
        'client_planning_call_completed_at' => 'completed',
        'fieldwork_client_calls_start_date' => 'date',
        'fieldwork_client_calls_end_date' => 'date',
        'fieldwork_client_calls_completed_at' => 'completed',
        'fieldwork_documentation_start_date' => 'date',
        'fieldwork_documentation_end_date' => 'date',
        'fieldwork_documentation_completed_at' => 'completed',
        'leadsheet_date' => 'date',
        'leadsheet_completed_at' => 'completed',
        'conclusion_memo_date' => 'date',
        'conclusion_memo_completed_at' => 'completed',
        'draft_report_due_date' => 'date',
        'draft_report_completed_at' => 'completed',
        'final_report_date' => 'date',
        'final_report_completed_at' => 'completed',
        'archive_date' => 'date',
        'archive_completed_at' => 'completed',
    ];
}

// True if the given user (defaults to whoever's logged in) is staffed on
// this engagement - either an `entries` row, or being the engagement's
// named manager (engagements.manager, a free-text name field someone can
// hold without ever personally logging hours). The manager branch only
// checks the current session's own name (every real caller only ever asks
// about the logged-in user - resolving an arbitrary other user's name
// would need an extra lookup nothing here actually needs). Used by every
// endpoint that used to just check `entries` alone and, as a result,
// silently locked a genuinely-assigned manager out of their own
// engagement's timeline/DOL/independence/planning doc until they happened
// to log an entry on it themselves.
function user_is_staffed_on_engagement($conn, $engagementId, $userId = null) {
    $userId = $userId !== null ? (int) $userId : (int) ($_SESSION['user_id'] ?? 0);
    if (!$userId) return false;

    $stmt = $conn->prepare("SELECT 1 FROM entries WHERE engagement_id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param('ii', $engagementId, $userId);
    $stmt->execute();
    $hasEntry = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    if ($hasEntry) return true;

    if ($userId !== (int) ($_SESSION['user_id'] ?? 0)) return false;
    $fullName = trim((string) ($_SESSION['full_name'] ?? ''));
    if ($fullName === '') return false;

    $stmt = $conn->prepare("SELECT 1 FROM engagements WHERE engagement_id = ? AND manager = ? LIMIT 1");
    $stmt->bind_param('is', $engagementId, $fullName);
    $stmt->execute();
    $isManager = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    return $isManager;
}

// Admins can act on any engagement. Everyone else needs the role permission
// AND to actually be staffed on this specific engagement.
function audit_can_act_on_engagement($conn, $engagementId, $permissionKey) {
    if (!user_has_permission($conn, $permissionKey)) return false;

    $role = strtolower($_SESSION['user_role'] ?? '');
    if ($role === 'admin') return true;

    return user_is_staffed_on_engagement($conn, $engagementId);
}
