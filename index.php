<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit();
} else {
    header("Location: auth/sso_login.php");
    exit();
}
?>
