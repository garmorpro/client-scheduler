<?php
header('Content-Type: application/json');
require_once '../includes/db.php'; // defines $host, $user, $pass, $dbname, $conn

$input = json_decode(file_get_contents('php://input'), true);
$backupDir = rtrim($input['local_backup_directory'] ?? '/tmp/db_backups', '/');

// Ensure backup directory exists
if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true)) {
    echo json_encode(['success' => false, 'error' => 'Failed to create backup directory']);
    exit;
}

// Generate timestamped filename
$timestamp = date('Y-m-d_His');
$backupFile = "$backupDir/db_backup_$timestamp.sql";

// Find mysqldump binary
$mysqldumpPath = trim(shell_exec('which mysqldump'));
if (!$mysqldumpPath) {
    echo json_encode(['success' => false, 'error' => 'mysqldump not found on server']);
    exit;
}

// Build command
$command = sprintf(
    '%s -h%s -u%s %s %s > %s 2>&1',
    escapeshellcmd($mysqldumpPath),
    escapeshellarg($host),
    escapeshellarg($user),
    $pass !== '' ? '-p' . escapeshellarg($pass) : '',
    escapeshellarg($dbname),
    escapeshellarg($backupFile)
);

// Run command
exec($command, $output, $returnVar);

// Check for errors
if ($returnVar !== 0) {
    echo json_encode([
        'success' => false,
        'error' => 'mysqldump failed',
        'details' => implode("\n", $output)
    ]);
    exit;
}

// Get backup size
$size = filesize($backupFile);
$sizeFormatted = $size >= 1073741824 ? round($size/1073741824,2) . ' GB' :
                 ($size >= 1048576 ? round($size/1048576,2) . ' MB' :
                 ($size >= 1024 ? round($size/1024,2) . ' KB' : $size . ' B'));

echo json_encode([
    'success' => true,
    'file' => $backupFile,
    'size' => $sizeFormatted
]);
