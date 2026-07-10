<?php
header('Content-Type: application/json');
require_once '../includes/db.php'; // defines $host, $user, $pass, $dbname, $conn
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/backup_helpers.php';

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'access_system_settings')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$backupDir = backup_resolve_dir($input['local_backup_directory'] ?? null);

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
