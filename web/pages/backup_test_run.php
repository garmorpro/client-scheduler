<?php
header('Content-Type: application/json');
require_once '../includes/db.php'; // $conn = new mysqli(...);

$input = json_decode(file_get_contents('php://input'), true);
$backupDir = rtrim($input['local_backup_directory'] ?? '/tmp/db_backups', '/');

if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true)) {
    echo json_encode(['success' => false, 'error' => 'Failed to create backup directory']);
    exit;
}

// --- Generate timestamped filename ---
$timestamp = date('Y-m-d_His');
$backupFile = "$backupDir/db_backup_$timestamp.sql";

// --- Get DB credentials from $conn ---
$host = $conn->host_info ?? 'localhost';
$dbName = $conn->query("SELECT DATABASE()")->fetch_row()[0] ?? '';
$dbUser = defined('DB_USER') ? DB_USER : ''; // define these in your db.php if needed
$dbPass = defined('DB_PASS') ? DB_PASS : '';

// --- Run mysqldump ---
$command = sprintf(
    'mysqldump -h%s -u%s %s %s > %s 2>&1',
    escapeshellarg($host),
    escapeshellarg($dbUser),
    $dbPass !== '' ? '-p' . escapeshellarg($dbPass) : '',
    escapeshellarg($dbName),
    escapeshellarg($backupFile)
);

exec($command, $output, $returnVar);

// Check if dump succeeded
if ($returnVar !== 0) {
    echo json_encode([
        'success' => false,
        'error' => 'mysqldump failed',
        'details' => implode("\n", $output)
    ]);
    exit;
}

// --- Get backup size ---
$size = filesize($backupFile);
$sizeFormatted = $size >= 1073741824 ? round($size/1073741824,2) . ' GB' :
                 ($size >= 1048576 ? round($size/1048576,2) . ' MB' :
                 ($size >= 1024 ? round($size/1024,2) . ' KB' : $size . ' B'));

echo json_encode([
    'success' => true,
    'file' => $backupFile,
    'size' => $sizeFormatted
]);
