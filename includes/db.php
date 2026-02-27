<?php
// date_default_timezone_set('America/Chicago');

// $host = getenv('DB_HOST');
// $user = getenv('DB_USER');
// $pass = getenv('DB_PASSWORD');
// $dbname = getenv('DB_NAME');

// $conn = new mysqli($host, $user, $pass, $dbname);
// if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

declare(strict_types=1);

date_default_timezone_set('America/Chicago');

use Dotenv\Dotenv;

// ---------------- LOAD .ENV -------------------
$dotenvPath = '/var/www/client-scheduler';
if (!file_exists($dotenvPath . '/.env')) {
    die(json_encode(['error' => 'Missing .env file', 'path' => $dotenvPath . '/.env']));
}

require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoload
$dotenv = Dotenv::createUnsafeImmutable($dotenvPath);
$dotenv->safeLoad();

var_dump($_ENV);
var_dump(getenv('DB_HOST'));
die();

$host = getenv('DB_HOST') ?: null;
$user = getenv('DB_USER') ?: null;
$pass = getenv('DB_PASSWORD') ?: null;
$dbname = getenv('DB_NAME') ?: null;

// Validate DB credentials
if (!$host || !$user || !$pass || !$dbname) {
    die(json_encode([
        'error' => 'Database credentials are missing or invalid',
        'DB_HOST' => $host,
        'DB_USER' => $user,
        'DB_NAME' => $dbname
    ]));
}

// ---------------- CONNECT TO DATABASE -------------------
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die(json_encode([
        'error' => 'Database connection failed',
        'message' => $conn->connect_error
    ]));
}

// Optional: set charset
$conn->set_charset('utf8mb4');
