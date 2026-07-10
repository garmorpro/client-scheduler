<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/backup_helpers.php';

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'access_system_settings')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$settingsRow = $conn->query("SELECT setting_value FROM settings WHERE setting_master_key = 'backup' AND setting_key = 'local_backup_directory'")->fetch_assoc();
$backupDir = backup_resolve_dir($settingsRow['setting_value'] ?? null);

$files = glob($backupDir . '/db_backup_*.sql') ?: [];
usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

$backups = array_map(function ($path) {
    $size = filesize($path);
    $sizeFormatted = $size >= 1073741824 ? round($size / 1073741824, 2) . ' GB' :
                      ($size >= 1048576 ? round($size / 1048576, 2) . ' MB' :
                      ($size >= 1024 ? round($size / 1024, 2) . ' KB' : $size . ' B'));
    return [
        'name' => basename($path),
        'size' => $sizeFormatted,
        'created' => date('M j, Y g:i A', filemtime($path)),
    ];
}, $files);

echo json_encode(['success' => true, 'backups' => $backups]);
