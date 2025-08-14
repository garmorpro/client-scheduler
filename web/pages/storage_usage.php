<!-- <?php
// Path to check (root folder of your app)
// $path = __DIR__; // current directory

// $totalSpace = disk_total_space($path);
// $freeSpace = disk_free_space($path);
// $usedSpace = $totalSpace - $freeSpace;

// function formatSize($bytes) {
//     $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
//     $i = 0;
//     while ($bytes >= 1024 && $i < count($sizes) - 1) {
//         $bytes /= 1024;
//         $i++;
//     }
//     return round($bytes, 2) . ' ' . $sizes[$i];
// }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Server Storage Usage</title>
</head>
<body>
    <h1>Server Storage Usage</h1>
    <p>Total Space: <?= //formatSize($totalSpace) ?></p>
    <p>Used Space: <?= //formatSize($usedSpace) ?></p>
    <p>Free Space: <?= //formatSize($freeSpace) ?></p>
</body>
</html> -->


<?php
$dbFile = __DIR__ . '/../includes/db.php'; // adjust relative path

// Try to include DB file
if (file_exists($dbFile)) {
    @require_once $dbFile; // suppress warnings
} else {
    $conn = null; // no connection available
}

// Default status
$dbStatus = "Database server is down ❌";

// Check if connection exists and is alive safely
if (isset($conn) && is_object($conn)) {
    try {
        if ($conn->ping()) {
            $dbStatus = "Database server is alive ✅";
        }
    } catch (Exception $e) {
        // Do nothing, keep $dbStatus as down
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>DB Status</title>
</head>
<body>
    <h1><?php $dbStatus ?></h1>
</body>
</html>