<?php
/**
 * Audit due-date notification cron. Run daily via cron, e.g.:
 *   0 8 * * * php /var/www/client-scheduler/pages/audit-notification-cron.php
 *
 * Checks audit_engagement_timeline / audit_engagement_milestones for
 * anything due in the next 1-7 days (5 for milestones) that hasn't been
 * emailed yet, and sends one combined digest email per person covering
 * everything due across every engagement they're staffed on. See
 * includes/audit_notifications.php for the actual logic.
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

auditCronLog($logFile, 'Starting audit notification cron job');

try {
    $sent = sendAuditDueDateDigests($conn);
    auditCronLog($logFile, "Sent {$sent} digest email(s)");
    auditCronLog($logFile, 'Audit notification cron job completed successfully');
} catch (\Throwable $e) {
    auditCronLog($logFile, 'ERROR: ' . $e->getMessage());
}
