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

    // Fetch user details from the database
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Prepare and execute the SQL query
        $query = "SELECT first_name, last_name FROM users WHERE email = ? AND password = ?";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("ss", $email, $password); // Bind the email and password to the query
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                // Fetch the user's details
                $stmt->bind_result($first_name, $last_name);
                $stmt->fetch();
                
                // Display the user's name
                echo "Welcome, " . htmlspecialchars($first_name) . " " . htmlspecialchars($last_name) . "!";
            } else {
                echo "Invalid login credentials.";
            }
            $stmt->close();
        } else {
            echo "Error preparing the SQL statement.";
        }
    }
}

$conn->close();
?>
