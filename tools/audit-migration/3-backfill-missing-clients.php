<?php
/**
 * Phase 0, step 3 (as-needed): backfill missing clients/engagements.
 *
 * Client Scheduler hasn't been rolled out yet, so its `clients` table is
 * mostly empty compared to Engagement Tracker's real roster — the
 * engagement crosswalk (script 2) will show a lot of "no client match" /
 * "client matched, no engagement row for that year" rows as a result. This
 * is the expected, bulk version of that gap, not a string of one-off
 * typos, so instead of hand-editing dozens of CSV rows, this creates the
 * missing `clients` + `engagements` rows directly.
 *
 * For each row in the LATEST engagement crosswalk full report where:
 *   - engagement_match_confidence = 'no client match' — creates both a new
 *     `clients` row and a new `engagements` row for it.
 *   - engagement_match_confidence = 'client matched, no engagement row for
 *     that year' — the client already exists; only creates the missing
 *     `engagements` row, using the crosswalk's already-resolved
 *     cs_client_id.
 * Anything else (exact match, ambiguous, fuzzy, year unknown) is left
 * alone — those need a human decision, not an auto-create.
 *
 * Best-effort links each new engagement to the CS audit_types it covers,
 * based on ET's eng_audit_type — unrecognized audit type names are
 * skipped and logged, not fatal.
 *
 * New rows are deliberately generic placeholders (status = 'not_confirmed',
 * budgeted/assigned hours = 0, manager = NULL) — go back and fill in the
 * real values in Client Scheduler's own UI afterward. The point here is
 * only to create a target for the migration to land in.
 *
 * SAFE BY DEFAULT: runs in a transaction, rolls back unless --commit.
 *
 * Usage (from the Client Scheduler project root):
 *   php tools/audit-migration/3-backfill-missing-clients.php [--commit]
 *
 * After running with --commit, RE-RUN 2-engagement-crosswalk.php — the
 * rows this created should now come back as exact matches, ready for
 * 4-migrate-data.php.
 */

require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/../../includes/db.php'; // $conn = Client Scheduler DB

$options = getopt('', ['engagements:', 'commit']);
$commit = isset($options['commit']);
$outDir = __DIR__ . '/output';

$engagementCsvPath = $options['engagements'] ?? findLatestCsv($outDir, 'engagement_crosswalk');
if (!$engagementCsvPath) {
    fwrite(STDERR, "Could not find an engagement crosswalk CSV. Run 2-engagement-crosswalk.php first.\n");
    exit(1);
}

echo "Using engagement crosswalk: $engagementCsvPath\n";
echo $commit ? "Mode: COMMIT (writes will be kept)\n\n" : "Mode: DRY RUN (writes will be rolled back)\n\n";

$rows = readCsvAsAssoc($engagementCsvPath);
$targets = array_filter($rows, fn($r) => in_array($r['engagement_match_confidence'], [
    'no client match',
    'client matched, no engagement row for that year',
], true));

echo "Rows to backfill: " . count($targets) . " of " . count($rows) . "\n\n";

// ET audit type -> CS audit_types.name, best-effort. ET's eng_audit_type
// is a free-text comma list; match case-insensitively against known names
// and a couple of likely abbreviations.
$auditTypeAliases = [
    'soc1' => 'SOC 1', 'soc 1' => 'SOC 1',
    'soc2' => 'SOC 2', 'soc 2' => 'SOC 2',
    'hipaa' => 'HIPAA',
    'hitrust' => 'HITRUST',
    'fisma' => 'FISMA',
];
$auditTypeIdByName = [];
$res = $conn->query("SELECT audit_type_id, name FROM audit_types");
while ($row = $res->fetch_assoc()) {
    $auditTypeIdByName[strtolower($row['name'])] = (int) $row['audit_type_id'];
}

$log = [];
function logAction(array &$log, string $etRef, string $action, string $detail = ''): void
{
    $log[] = ['et_ref' => $etRef, 'action' => $action, 'detail' => $detail];
    echo "$etRef: $action" . ($detail ? " ($detail)" : '') . "\n";
}

$conn->begin_transaction();

try {
    foreach ($targets as $row) {
        $etIdno = $row['et_eng_idno'];
        $year = $row['inferred_year'] !== '' ? (int) $row['inferred_year'] : null;

        if ($year === null) {
            logAction($log, $etIdno, 'SKIPPED', 'no inferred year, cannot create a yearly engagement row');
            continue;
        }

        // Map ET's lifecycle status to a note — CS's engagement status
        // (confirmed/pending/not_confirmed) means something different
        // (staffing confirmation, not audit lifecycle), so it's not a
        // real mapping, just left as 'not_confirmed' with the ET status
        // preserved in the notes for a human to read.
        $notes = "Backfilled from Engagement Tracker migration ({$etIdno}, status: {$row['et_status']}) — review and confirm.";

        if ($row['engagement_match_confidence'] === 'no client match') {
            // Does a client with this exact name already exist? (idempotent re-run)
            $existing = bindExecute($conn, "SELECT client_id FROM clients WHERE client_name = ?", [$row['et_eng_name']])->get_result()->fetch_assoc();
            if ($existing) {
                $clientId = (int) $existing['client_id'];
                logAction($log, $etIdno, 'client already exists, reusing', "client_id $clientId");
            } else {
                bindExecute($conn, "
                    INSERT INTO clients (client_name, onboarded_date, status, notes)
                    VALUES (?, CURDATE(), 'active', ?)
                ", [$row['et_eng_name'], $notes])->close();
                $clientId = $conn->insert_id;
                logAction($log, $etIdno, 'client created', "client_id $clientId");
            }
        } else {
            // 'client matched, no engagement row for that year' — crosswalk already resolved the client.
            if (empty($row['cs_client_id'])) {
                logAction($log, $etIdno, 'SKIPPED', 'expected cs_client_id from crosswalk but it was blank');
                continue;
            }
            $clientId = (int) $row['cs_client_id'];
        }

        // Does an engagement row for this client+year already exist? (idempotent re-run)
        $existingEng = bindExecute($conn, "SELECT engagement_id FROM engagements WHERE client_id = ? AND year = ?", [$clientId, $year])->get_result()->fetch_assoc();
        if ($existingEng) {
            $engagementId = (int) $existingEng['engagement_id'];
            logAction($log, $etIdno, 'engagement already exists, reusing', "engagement_id $engagementId");
        } else {
            bindExecute($conn, "
                INSERT INTO engagements (client_id, client_name, year, budgeted_hours, assigned_hours, manager, status, notes)
                VALUES (?, ?, ?, 0, 0, NULL, 'not_confirmed', ?)
            ", [$clientId, $row['et_eng_name'], $year, $notes])->close();
            $engagementId = $conn->insert_id;
            logAction($log, $etIdno, 'engagement created', "engagement_id $engagementId, year $year");
        }

        // Best-effort audit type links.
        $auditTypesRaw = array_filter(array_map('trim', explode(',', (string) ($row['et_audit_type'] ?? ''))));
        foreach ($auditTypesRaw as $rawType) {
            $key = strtolower($rawType);
            $auditTypeId = $auditTypeIdByName[$key] ?? $auditTypeIdByName[$auditTypeAliases[$key] ?? ''] ?? null;
            if (!$auditTypeId) {
                logAction($log, $etIdno, 'audit type not linked', "unrecognized: '$rawType'");
                continue;
            }
            bindExecute($conn, "
                INSERT IGNORE INTO engagement_audit_types (engagement_id, audit_type_id) VALUES (?, ?)
            ", [$engagementId, $auditTypeId])->close();
        }
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
    fwrite(STDERR, "\nBackfill failed, rolled back: " . $e->getMessage() . "\n");
    exit(1);
}

$timestamp = date('Y-m-d_His');
$logPath = "$outDir/backfill_log_{$timestamp}.csv";
$fh = openCsv($logPath, ['et_ref', 'action', 'detail']);
foreach ($log as $row) {
    fputcsv($fh, $row, ",", "\"", "\\");
}
fclose($fh);

echo "\nBackfill log: $logPath\n";
if ($commit) {
    echo "\nNow re-run 2-engagement-crosswalk.php — these should come back as exact matches.\n";
}
