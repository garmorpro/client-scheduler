<?php
require_once __DIR__ . '/includes/session_init.php';

if (isset($_SESSION['user_id'])) {
    header("Location: pages/master-schedule.php");
    exit();
} else {
    header("Location: auth/login.php");
    exit();
}
?>
