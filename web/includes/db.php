<?php
date_default_timezone_set('America/Chicago');

// Get DB credentials from environment
$host = getenv('DB_HOST');
$dbname   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');

// Turn off mysqli warnings (optional)
mysqli_report(MYSQLI_REPORT_OFF);

$db = new mysqli($host, $dbname, $user, $pass);
if ($db->connect_error) die("Connection failed: " . $db->connect_error);