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
 * Looks for values, in order: real environment variables prefixed ET_
 * (ET_DB_HOST etc.), then a `.env.et` file sitting next to this script
 * (gitignored — copy Engagement Tracker's own .env values into it, same
 * key names, since this file is loaded standalone).
 */
function connectSourceEngagementTracker(): mysqli
{
    $fromEnvFile = loadEnvFile(__DIR__ . '/.env.et');

    $host = getenv('ET_DB_HOST') ?: ($fromEnvFile['DB_HOST'] ?? null);
    $user = getenv('ET_DB_USER') ?: ($fromEnvFile['DB_USER'] ?? null);
    $pass = getenv('ET_DB_PASSWORD') ?: ($fromEnvFile['DB_PASSWORD'] ?? null);
    $name = getenv('ET_DB_NAME') ?: ($fromEnvFile['DB_NAME'] ?? null);

    if (!$host || !$user || $pass === null || !$name) {
        fwrite(STDERR, "Missing Engagement Tracker DB connection details.\n");
        fwrite(STDERR, "Set ET_DB_HOST / ET_DB_USER / ET_DB_PASSWORD / ET_DB_NAME as\n");
        fwrite(STDERR, "environment variables, or copy Engagement Tracker's .env values\n");
        fwrite(STDERR, "(DB_HOST / DB_USER / DB_PASSWORD / DB_NAME) into:\n");
        fwrite(STDERR, "  " . __DIR__ . "/.env.et\n");
        exit(1);
    }

    $conn = new mysqli($host, $user, $pass, $name);
    if ($conn->connect_error) {
        fwrite(STDERR, "Engagement Tracker DB connection failed: " . $conn->connect_error . "\n");
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
    fputcsv($fh, $header);
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
