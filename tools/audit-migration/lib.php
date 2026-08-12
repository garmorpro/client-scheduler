<?php
/**
 * Shared helpers for the Phase 0 crosswalk scripts (Engagement Tracker →
 * Client Scheduler audit-tracking migration). CLI only, no autoloader
 * needed beyond what db.php already pulls in.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Minimal KEY=VALUE file parser — deliberately NOT Dotenv/putenv(), so this
 * can load Engagement Tracker's connection details into their own isolated
 * array without colliding with Client Scheduler's own already-loaded
 * DB_HOST/DB_USER/etc. environment variables (both apps use the same names).
 *
 * Lines starting with # are comments; blank lines are skipped.
 */
function loadEnvFile(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Strip matching surrounding quotes if present.
        if (strlen($value) >= 2 && (
            ($value[0] === '"' && $value[-1] === '"') ||
            ($value[0] === "'" && $value[-1] === "'")
        )) {
            $value = substr($value, 1, -1);
        }
        $out[$key] = $value;
    }
    return $out;
}

/**
 * Resolves the Engagement Tracker source DB connection.
 *
 * Engagement Tracker and Client Scheduler live on two different servers
 * with two separate MySQL instances, neither reachable from the other's
 * network by default — so this almost always means an SSH tunnel, which
 * lands on a non-default local port. Hence ET_DB_PORT (defaults to 3306,
 * MySQL's normal port, for the rare case both really are on one host).
 *
 * Looks for values, in order: real environment variables prefixed ET_
 * (ET_DB_HOST etc.), then a `.env.et` file sitting next to this script
 * (gitignored — copy Engagement Tracker's own .env values into it, same
 * key names, plus DB_PORT if you're tunneling).
 */
function connectSourceEngagementTracker(): mysqli
{
    $fromEnvFile = loadEnvFile(__DIR__ . '/.env.et');

    $host = getenv('ET_DB_HOST') ?: ($fromEnvFile['DB_HOST'] ?? null);
    $user = getenv('ET_DB_USER') ?: ($fromEnvFile['DB_USER'] ?? null);
    $pass = getenv('ET_DB_PASSWORD') ?: ($fromEnvFile['DB_PASSWORD'] ?? null);
    $name = getenv('ET_DB_NAME') ?: ($fromEnvFile['DB_NAME'] ?? null);
    $port = (int) (getenv('ET_DB_PORT') ?: ($fromEnvFile['DB_PORT'] ?? 3306));

    if (!$host || !$user || $pass === null || !$name) {
        fwrite(STDERR, "Missing Engagement Tracker DB connection details.\n");
        fwrite(STDERR, "Set ET_DB_HOST / ET_DB_USER / ET_DB_PASSWORD / ET_DB_NAME\n");
        fwrite(STDERR, "(and ET_DB_PORT if tunneling) as environment variables, or copy\n");
        fwrite(STDERR, "Engagement Tracker's .env values (DB_HOST / DB_USER / DB_PASSWORD /\n");
        fwrite(STDERR, "DB_NAME, plus DB_PORT) into:\n");
        fwrite(STDERR, "  " . __DIR__ . "/.env.et\n");
        exit(1);
    }

    $conn = new mysqli($host, $user, $pass, $name, $port);
    if ($conn->connect_error) {
        fwrite(STDERR, "Engagement Tracker DB connection failed (host=$host port=$port): " . $conn->connect_error . "\n");
        exit(1);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

/** Opens a CSV file for writing and returns its handle, header row already written. */
function openCsv(string $path, array $header)
{
    $fh = fopen($path, 'w');
    if (!$fh) {
        fwrite(STDERR, "Could not open $path for writing.\n");
        exit(1);
    }
    fputcsv($fh, $header, ",", "\"", "\\");
    return $fh;
}

/**
 * Simple similarity score between two names, 0.0-1.0. Case/whitespace
 * insensitive. Used only to SUGGEST fuzzy matches for a human to confirm —
 * never to auto-resolve a match on its own.
 */
function nameSimilarity(string $a, string $b): float
{
    $a = strtolower(trim(preg_replace('/\s+/', ' ', $a)));
    $b = strtolower(trim(preg_replace('/\s+/', ' ', $b)));
    if ($a === '' || $b === '') {
        return 0.0;
    }
    if ($a === $b) {
        return 1.0;
    }
    $maxLen = max(strlen($a), strlen($b));
    if ($maxLen === 0) {
        return 0.0;
    }
    $dist = levenshtein($a, $b);
    return 1.0 - ($dist / $maxLen);
}

/**
 * Given one source name and a list of candidate ['id' => ..., 'name' => ...]
 * rows, returns the best match plus its score, or null if the list is empty.
 */
function bestNameMatch(string $sourceName, array $candidates): ?array
{
    $best = null;
    $bestScore = -1.0;
    foreach ($candidates as $candidate) {
        $score = nameSimilarity($sourceName, $candidate['name']);
        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $candidate;
        }
    }
    if ($best === null) {
        return null;
    }
    return ['candidate' => $best, 'score' => $bestScore];
}

/** Reads a CSV (as written by openCsv()) back into an array of associative rows. */
function readCsvAsAssoc(string $path): array
{
    if (!file_exists($path)) {
        fwrite(STDERR, "CSV not found: $path\n");
        exit(1);
    }
    $fh = fopen($path, 'r');
    $header = fgetcsv($fh, 0, ",", "\"", "\\");
    $rows = [];
    while (($line = fgetcsv($fh, 0, ",", "\"", "\\")) !== false) {
        if ($line === [null] || $line === false) {
            continue;
        }
        $rows[] = array_combine($header, $line);
    }
    fclose($fh);
    return $rows;
}

/**
 * Finds the most recently written CSV in $dir whose name starts with
 * $prefix, excluding *_UNMATCHED_* files (those are the review-only
 * subset, never the source of truth to migrate from). Returns null if none
 * found. Relies on the Y-m-d_His timestamp in the filename sorting
 * lexically the same as chronologically.
 */
function findLatestCsv(string $dir, string $prefix): ?string
{
    $matches = glob("$dir/{$prefix}_*.csv") ?: [];
    $matches = array_filter($matches, fn($f) => !str_contains(basename($f), 'UNMATCHED'));
    if (empty($matches)) {
        return null;
    }
    sort($matches);
    return end($matches);
}

/**
 * Prepares, binds, and executes a query against $params in one call,
 * WITHOUT a hand-written type string — the type character for each
 * parameter is derived from its actual PHP type (int -> 'i', float ->
 * 'd', everything else including null -> 's', which mysqli still binds as
 * a real SQL NULL). A hand-counted type string is exactly the kind of
 * off-by-one mistake that's easy to make and hard to spot in a 10+
 * parameter query, so every multi-param query in this migration goes
 * through here instead of calling bind_param() directly.
 */
function bindExecute(mysqli $conn, string $sql, array $params): mysqli_stmt
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new \RuntimeException('Prepare failed: ' . $conn->error . "\nSQL: $sql");
    }
    if (!empty($params)) {
        $types = implode('', array_map(
            fn($v) => is_int($v) ? 'i' : (is_float($v) ? 'd' : 's'),
            $params
        ));
        $refs = [$types];
        foreach ($params as $key => $value) {
            $refs[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    if (!$stmt->execute()) {
        throw new \RuntimeException('Execute failed: ' . $stmt->error . "\nSQL: $sql");
    }
    return $stmt;
}
