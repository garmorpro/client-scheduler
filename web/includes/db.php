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
    echo "Database connection successful!<br>";

    // Query to select the first user (or any specific user) from the users table
    $query = "SELECT first_name FROM users LIMIT 1";  // Adjust the query to your needs (e.g., specific user)
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        // Fetch and display the first user's first name
        $row = $result->fetch_assoc();
        echo "Database connection successful, " . $row['first_name'] . "!";
    } else {
        echo "No users found in the database.";
    }
}

// Close the connection
$conn->close();
?>
