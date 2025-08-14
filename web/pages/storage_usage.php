<?php
$path = '/'; // root directory of container
$bytes = trim(shell_exec("du -sb $path 2>/dev/null | cut -f1"));

function formatSize($bytes) {
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($sizes) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $sizes[$i];
}

echo "Approx. Container Data Usage: " . formatSize($bytes);
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
