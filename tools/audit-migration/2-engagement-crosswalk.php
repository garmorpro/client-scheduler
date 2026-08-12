<?php
/**
 * Phase 0, step 2: Engagement crosswalk.
 *
 * Engagement Tracker has no separate "client" entity — `eng_name` on its
 * `engagements` table IS the client identity (one row per audit cycle for
 * that client). Client Scheduler splits this into `clients` (the company)
 * and `engagements` (one row per client per year). So each ET engagement
 * has to resolve to BOTH a CS client and the right year's CS engagement row.
 *
 * Pulls ALL Engagement Tracker engagements — active and archived alike, per
 * the "migrate everything" decision — and matches each one to a Client
 * Scheduler client + engagement_id. Never auto-creates or auto-resolves;
 * anything short of a clean single match goes into the companion
 * *_UNMATCHED.csv for manual review before Phase 2 runs.
 *
 * Run from the Client Scheduler project root:
 *   php tools/audit-migration/2-engagement-crosswalk.php
 *
 * Requires the Engagement Tracker DB connection — see lib.php's
 * connectSourceEngagementTracker() for how to configure it.
 */

require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/../../includes/db.php'; // $conn = Client Scheduler DB

$etConn = connectSourceEngagementTracker();

// ---------------------------------------------------------------------
// Pull every Engagement Tracker engagement — no status filter, everything
// migrates, active and archived alike.
// ---------------------------------------------------------------------

$etEngagements = [];
$res = $etConn->query("
    SELECT eng_id, eng_idno, eng_name, eng_status, eng_audit_type,
           eng_start_period, eng_end_period, eng_as_of_date, eng_archive, eng_created
    FROM engagements
    ORDER BY eng_name, eng_created
");
if (!$res) {
    fwrite(STDERR, "Failed to read engagements: " . $etConn->error . "\n");
    exit(1);
}
while ($row = $res->fetch_assoc()) {
    $etEngagements[] = $row;
}

// ---------------------------------------------------------------------
// Pull Client Scheduler's clients + engagements (joined, so each candidate
// carries both the client name and the year it covers).
// ---------------------------------------------------------------------

$clients = []; // for name matching: ['id' => client_id, 'name' => client_name]
$csEngagementsByClient = []; // client_id => [ ['engagement_id'=>, 'year'=>, 'status'=>], ... ]

$res = $conn->query("SELECT client_id, client_name FROM clients ORDER BY client_name");
if (!$res) {
    fwrite(STDERR, "Failed to read clients: " . $conn->error . "\n");
    exit(1);
}
while ($row = $res->fetch_assoc()) {
    $clients[] = ['id' => $row['client_id'], 'name' => $row['client_name']];
}

$res = $conn->query("SELECT engagement_id, client_id, year, status FROM engagements ORDER BY client_id, year");
if (!$res) {
    fwrite(STDERR, "Failed to read engagements: " . $conn->error . "\n");
    exit(1);
}
while ($row = $res->fetch_assoc()) {
    $csEngagementsByClient[$row['client_id']][] = $row;
}

// ---------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------

function inferYear(array $eng): ?int
{
    foreach (['eng_as_of_date', 'eng_end_period', 'eng_start_period', 'eng_created'] as $field) {
        $val = $eng[$field] ?? null;
        if ($val && $val !== '0000-00-00' && preg_match('/^(\d{4})-/', $val, $m)) {
            return (int) $m[1];
        }
    }
    return null;
}

// ---------------------------------------------------------------------
// Match.
// ---------------------------------------------------------------------

$FUZZY_THRESHOLD = 0.82;

$fullReport = [];
$unmatched = [];

foreach ($etEngagements as $eng) {
    $year = inferYear($eng);
    $clientMatch = bestNameMatch($eng['eng_name'], $clients);

    $clientConfidence = 'no match';
    $csClientId = '';
    $csClientName = '';
    if ($clientMatch && $clientMatch['score'] === 1.0) {
        $clientConfidence = 'exact';
        $csClientId = $clientMatch['candidate']['id'];
        $csClientName = $clientMatch['candidate']['name'];
    } elseif ($clientMatch && $clientMatch['score'] >= $FUZZY_THRESHOLD) {
        $clientConfidence = 'fuzzy - needs confirmation';
        $csClientId = $clientMatch['candidate']['id'];
        $csClientName = $clientMatch['candidate']['name'];
    }

    $engagementConfidence = '';
    $csEngagementId = '';
    $notes = '';

    if ($clientConfidence === 'no match') {
        $engagementConfidence = 'no client match';
        $notes = 'No matching client in Client Scheduler at all — needs a new client (and engagement) created.';
    } elseif ($year === null) {
        $engagementConfidence = 'client matched, year unknown';
        $notes = 'Could not infer a year from eng_as_of_date/eng_end_period/eng_start_period/eng_created — check by hand.';
    } else {
        $candidates = array_values(array_filter(
            $csEngagementsByClient[$csClientId] ?? [],
            fn($row) => (int) $row['year'] === $year
        ));
        if (count($candidates) === 1) {
            $engagementConfidence = $clientConfidence === 'exact' ? 'exact' : 'fuzzy client - needs confirmation';
            $csEngagementId = $candidates[0]['engagement_id'];
        } elseif (count($candidates) === 0) {
            $engagementConfidence = 'client matched, no engagement row for that year';
            $notes = "No CS engagement row for client_id={$csClientId}, year={$year} — needs a new engagement row created there.";
        } else {
            $ids = implode('/', array_column($candidates, 'engagement_id'));
            $engagementConfidence = 'ambiguous - multiple engagement rows for that year';
            $notes = "Multiple candidate engagement_ids for year {$year}: {$ids} — pick the right one by hand.";
        }
    }

    $rowOut = [
        'et_eng_idno'       => $eng['eng_idno'],
        'et_eng_name'       => $eng['eng_name'],
        'et_status'         => $eng['eng_status'],
        'et_archived'       => ($eng['eng_archive'] && $eng['eng_archive'] !== '0000-00-00') ? 'Y' : 'N',
        'et_audit_type'     => $eng['eng_audit_type'],
        'inferred_year'     => $year ?? '',
        'client_match_confidence' => $clientConfidence,
        'cs_client_id'      => $csClientId,
        'cs_client_name'    => $csClientName,
        'engagement_match_confidence' => $engagementConfidence,
        'cs_engagement_id'  => $csEngagementId,
        'notes'             => $notes,
    ];

    $fullReport[] = $rowOut;
    if ($engagementConfidence !== 'exact') {
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
$fullPath = "$outDir/engagement_crosswalk_{$timestamp}.csv";
$unmatchedPath = "$outDir/engagement_crosswalk_UNMATCHED_{$timestamp}.csv";

$header = ['et_eng_idno', 'et_eng_name', 'et_status', 'et_archived', 'et_audit_type', 'inferred_year', 'client_match_confidence', 'cs_client_id', 'cs_client_name', 'engagement_match_confidence', 'cs_engagement_id', 'notes'];

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
echo "Engagement crosswalk complete.\n";
echo "  Total ET engagements checked: " . count($fullReport) . " (active + archived)\n";
echo "  Exact matches: $exactCount\n";
echo "  Needs review: " . count($unmatched) . "\n";
echo "  Full report:      $fullPath\n";
echo "  Needs-review only: $unmatchedPath\n";

if (count($unmatched) > 0) {
    echo "\nOpen the UNMATCHED file and resolve every row before Phase 2 runs.\n";
}
