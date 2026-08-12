<?php
/**
 * Phase 0, step 1: Identity crosswalk.
 *
 * Matches every name in Engagement Tracker's `employees` roster (and any
 * stray free-text name in `engagement_team` that never made it into the
 * roster) against Client Scheduler's real `users` table.
 *
 * This NEVER auto-resolves an ambiguous or missing match — it only
 * suggests. Anything short of an exact match goes into the companion
 * *_UNMATCHED.csv file, which is the required review artifact before Phase 2
 * (data migration) is allowed to run. See docs/client-scheduler-migration-plan.md
 * in the Engagement Tracker repo.
 *
 * Run from the Client Scheduler project root:
 *   php tools/audit-migration/1-identity-crosswalk.php
 *
 * Requires the Engagement Tracker DB connection — see lib.php's
 * connectSourceEngagementTracker() for how to configure it.
 */

require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/../../includes/db.php'; // $conn = Client Scheduler DB

$etConn = connectSourceEngagementTracker();

// ---------------------------------------------------------------------
// Pull source names from Engagement Tracker.
// ---------------------------------------------------------------------

$roster = [];
$res = $etConn->query("SELECT emp_id, emp_name, emp_role FROM employees ORDER BY emp_name");
if (!$res) {
    fwrite(STDERR, "Failed to read employees: " . $etConn->error . "\n");
    exit(1);
}
while ($row = $res->fetch_assoc()) {
    $roster[] = ['source' => 'employees', 'et_id' => $row['emp_id'], 'name' => $row['emp_name'], 'role' => $row['emp_role']];
}

// Names used on actual engagement assignments that never made it into the
// roster table (older free-text entries) — these need reconciling too.
$strayRes = $etConn->query("
    SELECT DISTINCT et.emp_name, et.role
    FROM engagement_team et
    LEFT JOIN employees e ON e.emp_name = et.emp_name
    WHERE e.emp_id IS NULL
    ORDER BY et.emp_name
");
if (!$strayRes) {
    fwrite(STDERR, "Failed to read engagement_team: " . $etConn->error . "\n");
    exit(1);
}
while ($row = $strayRes->fetch_assoc()) {
    $roster[] = ['source' => 'engagement_team (not in roster)', 'et_id' => null, 'name' => $row['emp_name'], 'role' => $row['role']];
}

// ---------------------------------------------------------------------
// Pull target candidates from Client Scheduler.
// ---------------------------------------------------------------------

$users = [];
$res = $conn->query("SELECT user_id, full_name, email, role, status FROM users ORDER BY full_name");
if (!$res) {
    fwrite(STDERR, "Failed to read users: " . $conn->error . "\n");
    exit(1);
}
while ($row = $res->fetch_assoc()) {
    $users[] = ['id' => $row['user_id'], 'name' => $row['full_name'], 'email' => $row['email'], 'role' => $row['role'], 'status' => $row['status']];
}

// ---------------------------------------------------------------------
// Match.
// ---------------------------------------------------------------------

$FUZZY_THRESHOLD = 0.82; // below this, treated as no match at all

$fullReport = [];
$unmatched = [];

foreach ($roster as $person) {
    $match = bestNameMatch($person['name'], $users);

    if ($match && $match['score'] === 1.0) {
        $confidence = 'exact';
    } elseif ($match && $match['score'] >= $FUZZY_THRESHOLD) {
        $confidence = 'fuzzy - needs confirmation';
    } else {
        $confidence = 'no match';
    }

    $cs = $match['candidate'] ?? null;
    $roleMatches = ($cs && strtolower($cs['role']) === strtolower((string) $person['role'])) ? 'Y' : ($cs ? 'N' : '');

    $rowOut = [
        'et_source'      => $person['source'],
        'et_id'          => $person['et_id'],
        'et_name'        => $person['name'],
        'et_role'        => $person['role'],
        'match_confidence' => $confidence,
        'match_score'    => $match ? round($match['score'], 3) : '',
        'cs_user_id'     => $cs['id'] ?? '',
        'cs_full_name'   => $cs['name'] ?? '',
        'cs_email'       => $cs['email'] ?? '',
        'cs_role'        => $cs['role'] ?? '',
        'role_matches'   => $roleMatches,
        'cs_status'      => $cs['status'] ?? '',
        'notes'          => '',
    ];

    $fullReport[] = $rowOut;
    if ($confidence !== 'exact') {
        $unmatched[] = $rowOut;
    }
}

// ---------------------------------------------------------------------
// Write output.
// ---------------------------------------------------------------------

$outDir = __DIR__ . '/output';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}
$timestamp = date('Y-m-d_His');
$fullPath = "$outDir/identity_crosswalk_{$timestamp}.csv";
$unmatchedPath = "$outDir/identity_crosswalk_UNMATCHED_{$timestamp}.csv";

$header = ['et_source', 'et_id', 'et_name', 'et_role', 'match_confidence', 'match_score', 'cs_user_id', 'cs_full_name', 'cs_email', 'cs_role', 'role_matches', 'cs_status', 'notes'];

$fh = openCsv($fullPath, $header);
foreach ($fullReport as $row) {
    fputcsv($fh, $row);
}
fclose($fh);

$fh = openCsv($unmatchedPath, $header);
foreach ($unmatched as $row) {
    fputcsv($fh, $row);
}
fclose($fh);

$exactCount = count($fullReport) - count($unmatched);
echo "Identity crosswalk complete.\n";
echo "  Total ET names checked: " . count($fullReport) . "\n";
echo "  Exact matches: $exactCount\n";
echo "  Needs review (fuzzy or no match): " . count($unmatched) . "\n";
echo "  Full report:      $fullPath\n";
echo "  Needs-review only: $unmatchedPath\n";

if (count($unmatched) > 0) {
    echo "\nOpen the UNMATCHED file and resolve every row before Phase 2 runs.\n";
}
