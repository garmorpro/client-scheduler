<?php
// Saves the audit notification schedule AND actually rewrites the server's
// crontab to match - the one genuinely more powerful thing this endpoint
// does compared to everything else in this app. Real security notes:
//   - hour/minute are validated against a strict allowlist before ever
//     touching a shell command or the crontab line - never raw user text.
//   - The script path is computed server-side (realpath(), not user
//     input) and still passed through escapeshellarg() as defense in depth.
//   - The crontab is rewritten via `crontab <tempfile>`, never via a
//     single interpolated shell string - exec() gets the tempfile path as
//     its own escaped argument, not concatenated into a larger command.
//   - Every other line in the existing crontab is preserved untouched -
//     only the one line we previously installed (identified by a unique
//     trailing marker comment) is replaced or removed, so this can't
//     clobber unrelated cron jobs already on the server.
//   - This modifies the crontab of whatever OS user the web server
//     process runs as (not necessarily whoever is logged into the app) -
//     reported back in the response so that's never ambiguous.
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'access_system_settings')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$enabled = !empty($input['enabled']);
$hour = filter_var($input['hour'] ?? null, FILTER_VALIDATE_INT);
$minute = filter_var($input['minute'] ?? null, FILTER_VALIDATE_INT);

if ($hour === false || $hour < 0 || $hour > 23 || !in_array($minute, [0, 15, 30, 45], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid time']);
    exit();
}

// --- Persist to settings (the cron script itself also checks 'enabled' as
// a second safety net, independent of whether the crontab line exists). ---
$pairs = ['enabled' => $enabled ? 'true' : 'false', 'hour' => (string) $hour, 'minute' => (string) $minute];
$stmt = $conn->prepare("
    INSERT INTO settings (setting_master_key, setting_key, setting_value) VALUES ('audit_notifications', ?, ?)
    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
");
foreach ($pairs as $key => $value) {
    $stmt->bind_param('ss', $key, $value);
    $stmt->execute();
}
$stmt->close();

// --- Rewrite the crontab entry to match. ---
$scriptPath = realpath(__DIR__ . '/audit-notification-cron.php');
if (!$scriptPath) {
    echo json_encode(['success' => false, 'error' => 'Could not resolve audit-notification-cron.php path on this server.']);
    exit();
}
$marker = '# AUDIT_NOTIFICATION_CRON';

$existing = shell_exec('crontab -l 2>/dev/null') ?? '';
$lines = array_values(array_filter(array_map('rtrim', explode("\n", $existing)), fn($l) => $l !== ''));
// Drop any line we previously installed - everything else stays untouched.
$lines = array_values(array_filter($lines, fn($l) => strpos($l, $marker) === false));

if ($enabled) {
    $lines[] = "{$minute} {$hour} * * * php " . escapeshellarg($scriptPath) . " >> /dev/null 2>&1 {$marker}";
}

$tmpFile = tempnam(sys_get_temp_dir(), 'cron');
file_put_contents($tmpFile, implode("\n", $lines) . "\n");
exec('crontab ' . escapeshellarg($tmpFile) . ' 2>&1', $cronOutput, $cronReturnCode);
unlink($tmpFile);

$osUser = trim(shell_exec('whoami') ?? 'unknown');

if ($cronReturnCode !== 0) {
    echo json_encode([
        'success' => false,
        'error' => "Settings saved, but updating the crontab for OS user '{$osUser}' failed: " . implode(' ', $cronOutput)
            . '. That user may not have cron access on this server (check /etc/cron.allow / /etc/cron.deny), or crontab isn\'t installed.',
    ]);
    exit();
}

$adminUserId = $_SESSION['user_id'] ?? null;
$adminEmail = $_SESSION['email'] ?? '';
$adminName = $_SESSION['full_name'] ?? '';
$description = $enabled
    ? sprintf("Audit notification cron scheduled for %02d:%02d daily (crontab of OS user '%s')", $hour, $minute, $osUser)
    : "Audit notification cron disabled and removed from crontab (OS user '{$osUser}')";
$logStmt = $conn->prepare("INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)");
if ($logStmt) {
    $eventType = 'audit_notification_schedule_updated';
    $title = 'Audit Notification Schedule Updated';
    $logStmt->bind_param('sissss', $eventType, $adminUserId, $adminEmail, $adminName, $title, $description);
    $logStmt->execute();
    $logStmt->close();
}

echo json_encode(['success' => true, 'os_user' => $osUser]);
