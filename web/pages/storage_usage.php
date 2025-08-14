<?php
$path = '/'; // Root filesystem for container/VM

$totalSpace = disk_total_space($path);
$freeSpace = disk_free_space($path);
$usedSpace = $totalSpace - $freeSpace;
$percentUsed = ($usedSpace / $totalSpace) * 100;

function formatSize($bytes) {
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($sizes) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $sizes[$i];
}
?>

<?php
$dbFile = __DIR__ . '/includes/db.php'; // adjust relative path

if (file_exists($dbFile)) {
    @require_once $dbFile; // suppress warnings if file fails
} else {
    $conn = null; // no connection
}

// Check if DB connection exists and is alive
if (isset($conn) && $conn && $conn->ping()) {
    echo "Database server is alive ✅";
} else {
    echo "Database server is down ❌";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Server Storage Usage</title>
</head>
<body>
    <h1>Server Storage Usage</h1>
    <p>Total Space: <?= formatSize($totalSpace) ?></p>
    <p>Used Space: <?= formatSize($usedSpace) ?> (<?= round($percentUsed, 2) ?>%)</p>
    <p>Free Space: <?= formatSize($freeSpace) ?></p>
</body>
</html>
