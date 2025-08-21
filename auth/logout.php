<?php
session_start();

// Set the message first
$_SESSION['message'] = isset($_GET['timeout']) 
    ? "You have been logged out due to inactivity." 
    : "You have been logged out.";

// Now clear other session data except message
foreach ($_SESSION as $key => $value) {
    if ($key !== 'message') {
        unset($_SESSION[$key]);
    }
}

// Redirect to login page
header("Location: /");
exit;
?>