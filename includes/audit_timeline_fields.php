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

// Admins can act on any engagement. Everyone else needs the role permission
// AND to actually be staffed on this specific engagement (an `entries` row),
// matching the scoping already used by engagement-details.php's own view
// check and by approve_time_off's manager-scoping elsewhere in the app.
function audit_can_act_on_engagement($conn, $engagementId, $permissionKey) {
    if (!user_has_permission($conn, $permissionKey)) return false;

    $role = strtolower($_SESSION['user_role'] ?? '');
    if ($role === 'admin') return true;

    $stmt = $conn->prepare("SELECT 1 FROM entries WHERE engagement_id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param('ii', $engagementId, $_SESSION['user_id']);
    $stmt->execute();
    $hasEntry = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    return $hasEntry;
}
