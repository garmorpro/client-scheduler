<?php
date_default_timezone_set('America/Chicago');

$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');

$db = new mysqli($host, $user, $pass, $dbname);
if ($db->connect_error) die("Connection failed: " . $db->connect_error);
