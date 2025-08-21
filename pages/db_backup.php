<?php
require_once '../includes/db.php'; // your DB connection

// --- Fetch backup settings ---
$settings = [];
$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_master_key='backup'");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// --- Exit if automated backups are disabled ---
if (empty($settings['enable_automated_backups']) || $settings['enable_automated_backups'] !== 'true') {
    exit("Automated backups disabled.\n");
}

// --- Backup settings ---
$backupFrequency = $settings['backup_frequency'] ?? 'daily';
$backupDir = rtrim($settings['local_backup_directory'] ?? '/tmp/db_backups', '/');
$retentionDays = (int)($settings['retention_period_days'] ?? 7);

// --- Check if we should run this backup now ---
$hourNow = (int)date('H');
$dayOfWeek = (int)date('N'); // 1=Mon, 7=Sun
$dayOfMonth = (int)date('j');

$runBackup = false;
switch ($backupFrequency) {
    case 'hourly':
        $runBackup = true;
        break;
    case 'daily':
        $backupHour = (int)($settings['backup_time'] ? explode(':', $settings['backup_time'])[0] : 0);
        $runBackup = ($hourNow === $backupHour);
        break;
    case 'weekly':
        $backupHour = (int)($settings['backup_time'] ? explode(':', $settings['backup_time'])[0] : 0);
        $runBackup = ($dayOfWeek === 1 && $hourNow === $backupHour); // Monday
        break;
    case 'monthly':
        $backupHour = (int)($settings['backup_time'] ? explode(':', $settings['backup_time'])[0] : 0);
        $runBackup = ($dayOfMonth === 1 && $hourNow === $backupHour);
        break;
}

// --- If not scheduled to run now, exit ---
if (!$runBackup) {
    exit("Not time for backup yet.\n");
}

// --- Ensure backup directory exists ---
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

// --- Generate filename ---
$timestamp = date('Y-m-d_His');
$backupFile = "$backupDir/db_backup_$timestamp.sql";

// --- Extract credentials from existing $conn ---
$dbHost = $conn->host_info ?? 'localhost';
$dbUser = $conn->user ?? 'root';
$dbPass = $conn->pass ?? '';
$dbName = $conn->query("SELECT DATABASE()")->fetch_row()[0] ?? 'client_scheduler';

// --- Run mysqldump using the current credentials ---
$command = sprintf(
    "mysqldump -h%s -u%s %s %s > %s",
    escapeshellarg($dbHost),
    escapeshellarg($dbUser),
    $dbPass !== '' ? "-p" . escapeshellarg($dbPass) : '',
    escapeshellarg($dbName),
    escapeshellarg($backupFile)
);
exec($command, $output, $returnVar);

if ($returnVar !== 0) {
    echo "Backup failed!\n";
    exit(1);
}

// --- Cleanup old backups beyond retention ---
$files = glob($backupDir . "/db_backup_*.sql");
$now = time();
foreach ($files as $file) {
    if (filemtime($file) < ($now - $retentionDays * 86400)) {
        unlink($file);
    }
}

echo "Backup successful: $backupFile\n";
