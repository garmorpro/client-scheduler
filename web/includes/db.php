<?php
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');

// Create a connection
$conn = new mysqli($host, $user, $pass, $db);

// Check if connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Database connection successful!";
}
?>
