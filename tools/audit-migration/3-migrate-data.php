<?php
/**
 * Phase 2: Data migration.
 *
 * Reads the (by now manually resolved) crosswalk CSVs from Phase 0 and
 * copies Engagement Tracker's audit-tracking data — timeline, milestones,
 * DOL, independence, training restrictions — into the six new Client
 * Scheduler tables from Phase 1. Every ET engagement is attempted, active
 * and archived alike, per the "migrate everything" decision.
 *
 * SAFE BY DEFAULT: runs inside a transaction and ROLLS BACK unless you pass
 * --commit. Run it once without --commit first, read the summary and the
 * migration log it writes, and only re-run with --commit once the numbers
 * look right.
 *
 * Anything it can't place — an unresolved name, an unresolved engagement, a
 * DOL criterion that's already assigned to someone else on the Client
 * Scheduler side — gets skipped and written to the migration log, never
 * silently dropped or guessed at.
 *
 * Usage (from the Client Scheduler project root):
 *   php tools/audit-migration/3-migrate-data.php [options]
 *
 * Options:
 *   --identity=PATH      Path to the resolved identity crosswalk CSV.
 *                         Defaults to the newest non-UNMATCHED file in
 *                         tools/audit-migration/output/.
 *   --engagements=PATH   Path to the resolved engagement crosswalk CSV.
 *                         Same default rule.
 *   --commit             Actually commit the transaction. Without this,
 *                         everything runs and is reported, then rolled back.
 *
 * Before running: every row in both *_UNMATCHED.csv files from Phase 0 must
 * be resolved — either the full crosswalk CSV has been hand-edited to fill
 * in the correct cs_user_id / cs_engagement_id, or the row is intentionally
 * left blank to skip it. Rows with a blank cs_user_id / cs_engagement_id
 * are skipped here, not treated as an error — that's the mechanism for
 * deliberately excluding something.
 */

require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/../../includes/db.php'; // $conn = Client Scheduler DB (target)

$etConn = connectSourceEngagementTracker();

function normalizeName(string $n): string
{
    return strtolower(trim(preg_replace('/\s+/', ' ', $n)));
}

// ---------------------------------------------------------------------
// CLI args
// ---------------------------------------------------------------------

$options = getopt('', ['identity:', 'engagements:', 'commit']);
$commit = isset($options['commit']);
$outDir = __DIR__ . '/output';

$identityCsvPath = $options['identity'] ?? findLatestCsv($outDir, 'identity_crosswalk');
$engagementCsvPath = $options['engagements'] ?? findLatestCsv($outDir, 'engagement_crosswalk');

if (!$identityCsvPath || !$engagementCsvPath) {
    fwrite(STDERR, "Could not find crosswalk CSVs. Run 1-identity-crosswalk.php and\n");
    fwrite(STDERR, "2-engagement-crosswalk.php first, or pass --identity=PATH --engagements=PATH.\n");
    exit(1);
}

echo "Using identity crosswalk:   $identityCsvPath\n";
echo "Using engagement crosswalk: $engagementCsvPath\n";
echo $commit ? "Mode: COMMIT (writes will be kept)\n\n" : "Mode: DRY RUN (writes will be rolled back)\n\n";

// ---------------------------------------------------------------------
// Build lookup maps from the crosswalk CSVs. Only rows with a resolved
// target ID are usable — everything else is a deliberate or unresolved
// skip, logged below as it's encountered.
// ---------------------------------------------------------------------

$identityRows = readCsvAsAssoc($identityCsvPath);
$nameToUserId = [];
foreach ($identityRows as $row) {
    if (!empty($row['cs_user_id'])) {
        $nameToUserId[normalizeName($row['et_name'])] = (int) $row['cs_user_id'];
    }
}

$engagementRows = readCsvAsAssoc($engagementCsvPath);
$idnoToEngagementId = [];
foreach ($engagementRows as $row) {
    if (!empty($row['cs_engagement_id'])) {
        $idnoToEngagementId[$row['et_eng_idno']] = (int) $row['cs_engagement_id'];
    }
}

echo "Resolved identities: " . count($nameToUserId) . " / " . count($identityRows) . "\n";
echo "Resolved engagements: " . count($idnoToEngagementId) . " / " . count($engagementRows) . "\n\n";

// ---------------------------------------------------------------------
// Audit type name -> CS audit_type_id. ET only ever tracked these five;
// if any is missing on the CS side the migration stops rather than
// silently dropping DOL data for that framework.
// ---------------------------------------------------------------------

$dolFieldToAuditTypeName = [
    'emp_soc1_dol'    => 'SOC 1',
    'emp_soc2_dol'    => 'SOC 2',
    'emp_hipaa_dol'   => 'HIPAA',
    'emp_hitrust_dol' => 'HITRUST',
    'emp_fisma_dol'   => 'FISMA',
];

$auditTypeIdByName = [];
$res = $conn->query("SELECT audit_type_id, name FROM audit_types");
while ($row = $res->fetch_assoc()) {
    $auditTypeIdByName[$row['name']] = (int) $row['audit_type_id'];
}
foreach (array_unique(array_values($dolFieldToAuditTypeName)) as $neededName) {
    if (!isset($auditTypeIdByName[$neededName])) {
        fwrite(STDERR, "Client Scheduler is missing an audit_types row named '$neededName' — cannot migrate DOL until it exists.\n");
        exit(1);
    }
}

// ---------------------------------------------------------------------
// Migration log — every row is one action taken or skipped, regardless of
// dry-run/commit. Written out at the end no matter what.
// ---------------------------------------------------------------------

$log = [];
function logAction(array &$log, string $area, string $etRef, string $action, string $detail = ''): void
{
    $log[] = ['area' => $area, 'et_ref' => $etRef, 'action' => $action, 'detail' => $detail];
    echo "[$area] $etRef: $action" . ($detail ? " ($detail)" : '') . "\n";
}

$conn->begin_transaction();

try {
    // -------------------------------------------------------------
    // Per-engagement migration.
    // -------------------------------------------------------------
    foreach ($idnoToEngagementId as $etIdno => $csEngagementId) {
        // --- Source engagement row (for audit_engagement_details) ---
        $stmt = bindExecute($etConn, "SELECT * FROM engagements WHERE eng_idno = ?", [$etIdno]);
        $eng = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$eng) {
            logAction($log, 'engagement', $etIdno, 'SKIPPED', 'not found in Engagement Tracker (crosswalk stale?)');
            continue;
        }

        // --- audit_engagement_details ---
        bindExecute($conn, "
            INSERT INTO audit_engagement_details
                (engagement_id, location, poc, tsc, soc_type, scope, as_of_date, review_period_start, review_period_end, repeat_flag, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                location = VALUES(location), poc = VALUES(poc), tsc = VALUES(tsc),
                soc_type = VALUES(soc_type), scope = VALUES(scope), as_of_date = VALUES(as_of_date),
                review_period_start = VALUES(review_period_start), review_period_end = VALUES(review_period_end),
                repeat_flag = VALUES(repeat_flag), notes = VALUES(notes)
        ", [
            $csEngagementId, $eng['eng_location'], $eng['eng_poc'], $eng['eng_tsc'], $eng['eng_soc_type'],
            $eng['eng_scope'], $eng['eng_as_of_date'], $eng['eng_start_period'], $eng['eng_end_period'],
            $eng['eng_repeat'] ? 1 : 0, $eng['eng_notes'],
        ])->close();

        logAction($log, 'details', $etIdno, 'migrated');

        // --- audit_engagement_timeline ---
        $stmt = bindExecute($etConn, "SELECT * FROM engagement_timeline WHERE engagement_idno = ?", [$etIdno]);
        $tl = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($tl) {
            bindExecute($conn, "
                INSERT INTO audit_engagement_timeline (
                    engagement_id,
                    internal_planning_call_date, internal_planning_call_completed_at,
                    planning_memo_date, planning_memo_completed_at,
                    irl_due_date, irl_completed_at,
                    client_planning_call_date, client_planning_call_completed_at,
                    fieldwork_client_calls_start_date, fieldwork_client_calls_end_date, fieldwork_client_calls_completed_at,
                    fieldwork_documentation_start_date, fieldwork_documentation_end_date, fieldwork_documentation_completed_at,
                    leadsheet_date, leadsheet_completed_at,
                    conclusion_memo_date, conclusion_memo_completed_at,
                    draft_report_due_date, draft_report_completed_at,
                    final_report_date, final_report_completed_at,
                    archive_date, archive_completed_at,
                    weekly_status_call_day, weekly_status_call_group, weekly_status_call_group_name
                ) VALUES (?, ?,?, ?,?, ?,?, ?,?, ?,?,?, ?,?,?, ?,?, ?,?, ?,?, ?,?, ?,?, ?,?,?)
                ON DUPLICATE KEY UPDATE
                    internal_planning_call_date = VALUES(internal_planning_call_date),
                    internal_planning_call_completed_at = VALUES(internal_planning_call_completed_at),
                    planning_memo_date = VALUES(planning_memo_date),
                    planning_memo_completed_at = VALUES(planning_memo_completed_at),
                    irl_due_date = VALUES(irl_due_date),
                    irl_completed_at = VALUES(irl_completed_at),
                    client_planning_call_date = VALUES(client_planning_call_date),
                    client_planning_call_completed_at = VALUES(client_planning_call_completed_at),
                    fieldwork_client_calls_start_date = VALUES(fieldwork_client_calls_start_date),
                    fieldwork_client_calls_end_date = VALUES(fieldwork_client_calls_end_date),
                    fieldwork_client_calls_completed_at = VALUES(fieldwork_client_calls_completed_at),
                    fieldwork_documentation_start_date = VALUES(fieldwork_documentation_start_date),
                    fieldwork_documentation_end_date = VALUES(fieldwork_documentation_end_date),
                    fieldwork_documentation_completed_at = VALUES(fieldwork_documentation_completed_at),
                    leadsheet_date = VALUES(leadsheet_date),
                    leadsheet_completed_at = VALUES(leadsheet_completed_at),
                    conclusion_memo_date = VALUES(conclusion_memo_date),
                    conclusion_memo_completed_at = VALUES(conclusion_memo_completed_at),
                    draft_report_due_date = VALUES(draft_report_due_date),
                    draft_report_completed_at = VALUES(draft_report_completed_at),
                    final_report_date = VALUES(final_report_date),
                    final_report_completed_at = VALUES(final_report_completed_at),
                    archive_date = VALUES(archive_date),
                    archive_completed_at = VALUES(archive_completed_at),
                    weekly_status_call_day = VALUES(weekly_status_call_day),
                    weekly_status_call_group = VALUES(weekly_status_call_group),
                    weekly_status_call_group_name = VALUES(weekly_status_call_group_name)
            ", [
                $csEngagementId,
                $tl['internal_planning_call_date'], $tl['internal_planning_call_completed_at'],
                $tl['planning_memo_date'], $tl['planning_memo_completed_at'],
                $tl['irl_due_date'], $tl['irl_completed_at'],
                $tl['client_planning_call_date'], $tl['client_planning_call_completed_at'],
                $tl['fieldwork_client_calls_start_date'], $tl['fieldwork_client_calls_end_date'], $tl['fieldwork_client_calls_completed_at'],
                $tl['fieldwork_documentation_start_date'], $tl['fieldwork_documentation_end_date'], $tl['fieldwork_documentation_completed_at'],
                $tl['leadsheet_date'], $tl['leadsheet_completed_at'],
                $tl['conclusion_memo_date'], $tl['conclusion_memo_completed_at'],
                $tl['draft_report_due_date'], $tl['draft_report_completed_at'],
                $tl['final_report_date'], $tl['final_report_completed_at'],
                $tl['archive_date'], $tl['archive_completed_at'],
                $tl['weekly_status_call_day'], $tl['weekly_status_call_group'], $tl['weekly_status_call_group_name'],
            ])->close();
            logAction($log, 'timeline', $etIdno, 'migrated');
        } else {
            logAction($log, 'timeline', $etIdno, 'SKIPPED', 'no engagement_timeline row in Engagement Tracker');
        }

        // --- audit_engagement_milestones ---
        $stmt = bindExecute($etConn, "SELECT milestone_type, due_date, is_completed FROM engagement_milestones WHERE engagement_idno = ?", [$etIdno]);
        $milestones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($milestones as $ms) {
            $checkStmt = bindExecute($conn, "
                SELECT milestone_id FROM audit_engagement_milestones
                WHERE engagement_id = ? AND milestone_type = ? AND due_date <=> ?
            ", [$csEngagementId, $ms['milestone_type'], $ms['due_date']]);
            $already = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();
            if ($already) {
                continue; // idempotent re-run, already migrated
            }
            bindExecute($conn, "
                INSERT INTO audit_engagement_milestones (engagement_id, milestone_type, due_date, is_completed)
                VALUES (?, ?, ?, ?)
            ", [$csEngagementId, $ms['milestone_type'], $ms['due_date'], $ms['is_completed'] === 'Y' ? 1 : 0])->close();
        }
        if (!empty($milestones)) {
            logAction($log, 'milestones', $etIdno, 'migrated', count($milestones) . ' row(s)');
        }

        // --- Team: DOL + independence ---
        $stmt = bindExecute($etConn, "SELECT * FROM engagement_team WHERE engagement_idno = ?", [$etIdno]);
        $teamRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($teamRows as $member) {
            $userId = $nameToUserId[normalizeName($member['emp_name'])] ?? null;
            if (!$userId) {
                logAction($log, 'team-member', "$etIdno / {$member['emp_name']}", 'SKIPPED', 'no resolved cs_user_id in identity crosswalk');
                continue;
            }

            // Independence
            if ($member['emp_independent'] === 'Y' || $member['emp_independent'] === 'N') {
                bindExecute($conn, "
                    INSERT INTO audit_team_independence (engagement_id, user_id, independent)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE independent = VALUES(independent)
                ", [$csEngagementId, $userId, $member['emp_independent']])->close();
                logAction($log, 'independence', "$etIdno / {$member['emp_name']}", 'migrated', $member['emp_independent']);
            }

            // DOL
            foreach ($dolFieldToAuditTypeName as $field => $auditTypeName) {
                $csv = trim((string) ($member[$field] ?? ''));
                if ($csv === '') {
                    continue;
                }
                $auditTypeId = $auditTypeIdByName[$auditTypeName];
                foreach (array_filter(array_map('trim', explode(',', $csv))) as $criterion) {
                    $checkStmt = bindExecute($conn, "
                        SELECT user_id FROM audit_dol_assignments
                        WHERE engagement_id = ? AND audit_type_id = ? AND criterion = ?
                    ", [$csEngagementId, $auditTypeId, $criterion]);
                    $existing = $checkStmt->get_result()->fetch_assoc();
                    $checkStmt->close();

                    if ($existing && (int) $existing['user_id'] !== $userId) {
                        logAction($log, 'dol', "$etIdno / $auditTypeName $criterion", 'CONFLICT', "already assigned to user_id {$existing['user_id']}, ET has {$member['emp_name']} (user_id $userId) — left as-is, resolve by hand");
                        continue;
                    }
                    if ($existing) {
                        continue; // already correctly migrated
                    }

                    bindExecute($conn, "
                        INSERT INTO audit_dol_assignments (engagement_id, user_id, audit_type_id, criterion)
                        VALUES (?, ?, ?, ?)
                    ", [$csEngagementId, $userId, $auditTypeId, $criterion])->close();
                }
            }
        }
        if (!empty($teamRows)) {
            logAction($log, 'dol+independence', $etIdno, 'processed', count($teamRows) . ' team row(s)');
        }
    }

    // -------------------------------------------------------------
    // Training restrictions — roster-level, not per engagement. Runs once
    // for every roster name with a resolved cs_user_id.
    // -------------------------------------------------------------
    $res = $etConn->query("SELECT emp_name, emp_restricted_criteria FROM employees WHERE emp_restricted_criteria IS NOT NULL AND emp_restricted_criteria <> ''");
    while ($row = $res->fetch_assoc()) {
        $userId = $nameToUserId[normalizeName($row['emp_name'])] ?? null;
        if (!$userId) {
            logAction($log, 'training-restriction', $row['emp_name'], 'SKIPPED', 'no resolved cs_user_id');
            continue;
        }
        foreach (array_filter(array_map('trim', explode(',', $row['emp_restricted_criteria']))) as $criterion) {
            bindExecute($conn, "
                INSERT IGNORE INTO dol_training_restrictions (user_id, criterion) VALUES (?, ?)
            ", [$userId, $criterion])->close();
        }
        logAction($log, 'training-restriction', $row['emp_name'], 'migrated');
    }

    if ($commit) {
        $conn->commit();
        echo "\nCommitted.\n";
    } else {
        $conn->rollback();
        echo "\nDry run — rolled back. Re-run with --commit to keep these writes.\n";
    }
} catch (\Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, "\nMigration failed, rolled back: " . $e->getMessage() . "\n");
    exit(1);
}

// ---------------------------------------------------------------------
// Write the migration log regardless of outcome.
// ---------------------------------------------------------------------

$timestamp = date('Y-m-d_His');
$logPath = "$outDir/migration_log_{$timestamp}.csv";
$fh = openCsv($logPath, ['area', 'et_ref', 'action', 'detail']);
foreach ($log as $row) {
    fputcsv($fh, $row, ",", "\"", "\\");
}
fclose($fh);

$skipped = count(array_filter($log, fn($r) => $r['action'] === 'SKIPPED'));
$conflicts = count(array_filter($log, fn($r) => $r['action'] === 'CONFLICT'));

echo "\nMigration log: $logPath\n";
echo "  Skipped: $skipped\n";
echo "  Conflicts needing manual resolution: $conflicts\n";
