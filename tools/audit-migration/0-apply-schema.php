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
 * Not idempotent — the underlying SQL is all CREATE TABLE / ADD COLUMN, so
 * running this twice against the same database will error out on the
 * second run (table/column already exists). That's intentional: it's the
 * signal that this step is already done.
 */

require_once __DIR__ . '/lib.php'; // just for the CLI guard, no ET connection needed here
require_once __DIR__ . '/../../includes/db.php'; // $conn = Client Scheduler DB

$sqlPath = __DIR__ . '/../../storage/migrations/2026-08-12_add_audit_tracking_schema.sql';

if (!file_exists($sqlPath)) {
    fwrite(STDERR, "Migration file not found: $sqlPath\n");
    exit(1);
}

$sql = file_get_contents($sqlPath);

echo "Applying $sqlPath ...\n\n";

if (!$conn->multi_query($sql)) {
    fwrite(STDERR, "Failed: " . $conn->error . "\n");
    exit(1);
}

$statementNum = 0;
do {
    $statementNum++;
    if ($result = $conn->store_result()) {
        $result->free();
    }
    if ($conn->errno) {
        fwrite(STDERR, "Statement $statementNum failed: " . $conn->error . "\n");
        exit(1);
    }
    echo "  Statement $statementNum: OK\n";
} while ($conn->more_results() && $conn->next_result());

echo "\nSchema applied successfully.\n";
