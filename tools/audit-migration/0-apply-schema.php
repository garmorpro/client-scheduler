<?php
/**
 * Phase 1: applies the schema migration to Client Scheduler's own database.
 *
 * Runs storage/migrations/2026-08-12_add_audit_tracking_schema.sql through
 * Client Scheduler's existing DB connection (includes/db.php) — no `mysql`
 * CLI needed, since it isn't guaranteed to be installed wherever this runs.
 * Only touches the Client Scheduler DB; no Engagement Tracker connection
 * involved in this step at all.
 *
 * Run once, from the Client Scheduler project root:
 *   php tools/audit-migration/0-apply-schema.php
 *
 * Safe to re-run. Every CREATE TABLE in the .sql file uses IF NOT EXISTS
 * (universally supported). The role_permissions ADD COLUMN statement does
 * NOT — that syntax needs MySQL 8.0.29+, which isn't a safe assumption for
 * every server this might run on — so re-run safety for that one is
 * handled here instead: a "duplicate column" error (1060) is treated as
 * already-applied and skipped, not a failure. Statements run one at a
 * time (not via multi_query) so that if something ELSE fails, you get the
 * exact statement text that failed, not just a number to cross-reference
 * by hand.
 */

require_once __DIR__ . '/lib.php'; // just for the CLI guard, no ET connection needed here
require_once __DIR__ . '/../../includes/db.php'; // $conn = Client Scheduler DB

$sqlPath = __DIR__ . '/../../storage/migrations/2026-08-12_add_audit_tracking_schema.sql';

if (!file_exists($sqlPath)) {
    fwrite(STDERR, "Migration file not found: $sqlPath\n");
    exit(1);
}

$raw = file_get_contents($sqlPath);

// Strip full-line `--` comments, then split on statement-terminating
// semicolons. None of the statements in this file contain a literal `;`
// inside a string/comment, so this simple split is safe here — this is
// not a general-purpose SQL parser.
$withoutComments = preg_replace('/^--.*$/m', '', $raw);
$statements = array_values(array_filter(
    array_map('trim', explode(';', $withoutComments)),
    fn($s) => $s !== ''
));

echo "Applying $sqlPath (" . count($statements) . " statement(s)) ...\n\n";

const ER_DUP_FIELDNAME = 1060; // "Duplicate column name" — ADD COLUMN already applied
const ER_TABLE_EXISTS_ERROR = 1050; // belt-and-suspenders; CREATE TABLE IF NOT EXISTS should prevent this

foreach ($statements as $i => $statement) {
    $num = $i + 1;
    try {
        $conn->query($statement);
        echo "  Statement $num: OK\n";
    } catch (\mysqli_sql_exception $e) {
        if (in_array($e->getCode(), [ER_DUP_FIELDNAME, ER_TABLE_EXISTS_ERROR], true)) {
            echo "  Statement $num: already applied, skipping (" . $e->getMessage() . ")\n";
            continue;
        }
        fwrite(STDERR, "\nStatement $num failed: " . $e->getMessage() . "\n\n");
        fwrite(STDERR, "--- Failing statement ---\n$statement\n-------------------------\n");
        exit(1);
    }
}

echo "\nSchema applied successfully.\n";
