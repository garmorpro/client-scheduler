<?php
session_start();
session_unset();
session_destroy();

$_SESSION['message'] = isset($_GET['timeout']) 
    ? "You have been logged out due to inactivity." 
    : "You have been logged out.";

header("Location: /");
exit;
?>
