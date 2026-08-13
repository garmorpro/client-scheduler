<?php
/**
 * Audit due-date notification cron. Scheduling is managed from Settings ->
 * Audit Notification Schedule (writes/removes the crontab entry directly),
 * not by hand-editing crontab - see pages/update-audit-notification-schedule.php.
 *
 * Checks audit_engagement_timeline / audit_engagement_milestones for
 * anything due in the next 1-7 days (5 for milestones) that hasn't been
 * emailed yet, and sends one combined digest email per person covering
 * everything due across every engagement they're staffed on. See
 * includes/audit_notifications.php for the actual logic.
 *
 * --force skips the 'enabled' settings check below, for a manual test run
 * regardless of the current toggle state.
 * --dry-run shows what WOULD be sent (recipients + items, printed here to
 * the terminal) without actually emailing anything or touching the dedup
 * log - safe to run repeatedly while testing. Combine with --force to see
 * results regardless of the enabled toggle: `--dry-run --force`.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Forbidden: this script may only be run from the command line (e.g. via cron).\n");
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit_notifications.php';

$logFile = __DIR__ . '/../logs/audit-notification-cron.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}
function auditCronLog(string $file, string $message): void
{
    file_put_contents($file, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

$force = in_array('--force', $argv ?? [], true);
$dryRun = in_array('--dry-run', $argv ?? [], true);

// Belt-and-suspenders: the schedule page removes the crontab entry
// entirely when disabled, but check the DB flag too in case the crontab
// and the setting ever drift (e.g. someone edited cron by hand). Defaults
// to enabled if the setting was never set at all, so a crontab entry
// installed manually (before this toggle existed) keeps working.
if (!$force) {
    $res = $conn->query("SELECT setting_value FROM settings WHERE setting_master_key = 'audit_notifications' AND setting_key = 'enabled'");
    $row = $res ? $res->fetch_assoc() : null;
    if ($row && $row['setting_value'] === 'false') {
        auditCronLog($logFile, 'Skipped - disabled in Settings');
        exit(0);
    }
}

auditCronLog($logFile, 'Starting audit notification cron job' . ($dryRun ? ' (DRY RUN)' : ''));

try {
    $results = sendAuditDueDateDigests($conn, $dryRun);

    if ($dryRun) {
        echo "DRY RUN - nothing was emailed, nothing was marked as notified.\n\n";
    }

    if (empty($results)) {
        echo "Nothing currently due in the notification window (1-7 days for key dates, 1-5 for milestones) that hasn't already been notified.\n";
    }
    foreach ($results as $r) {
        $status = $dryRun ? 'would send' : ($r['sent'] ? 'sent' : 'FAILED to send (check email settings)');
        echo sprintf("  %s <%s> — %s: %d item(s)\n", $r['name'], $r['email'], $status, count($r['items']));
        foreach ($r['items'] as $item) {
            echo sprintf("      - %s: %s due %s (%s)\n", $item['client_name'], $item['title'], $item['date_label'], $item['days_away']);
        }
    }

    $sentCount = count(array_filter($results, fn($r) => $r['sent']));
    auditCronLog($logFile, ($dryRun ? 'Dry run: would send' : 'Sent') . " {$sentCount}/" . count($results) . ' digest email(s)');
    auditCronLog($logFile, 'Audit notification cron job completed successfully');
} catch (\Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    auditCronLog($logFile, 'ERROR: ' . $e->getMessage());
}
