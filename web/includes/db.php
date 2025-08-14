<?php
date_default_timezone_set('America/Chicago');

// Get DB credentials from environment
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = fil;

// Turn off mysqli warnings (optional)
mysqli_report(MYSQLI_REPORT_OFF);

// Try to connect
$conn = @new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    // Connection failed, but don't die — just set $conn to null
    $conn = null;
    // Optional: log the error somewhere for debugging
    // error_log("DB connection failed: " . $conn->connect_error);
}
?>
