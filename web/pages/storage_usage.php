<?php
// Path to check (root folder of your app)
$path = __DIR__; // current directory

$totalSpace = disk_total_space($path);
$freeSpace = disk_free_space($path);
$usedSpace = $totalSpace - $freeSpace;

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
<!DOCTYPE html>
<html>
<head>
    <title>Server Storage Usage</title>
</head>
<body>
    <h1>Server Storage Usage</h1>
    <p>Total Space: <?= formatSize($totalSpace) ?></p>
    <p>Used Space: <?= formatSize($usedSpace) ?></p>
    <p>Free Space: <?= formatSize($freeSpace) ?></p>
</body>
</html>
