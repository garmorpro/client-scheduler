<?php
require_once '../includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Client Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: rgba(216, 216, 216, 1);">
<div class="container h-100 d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card p-4 shadow" style="width: 100%; max-width: 425px;">
        <img src="../assets/images/aarc-360-logo-1.webp" alt="Logo" class="mx-auto d-block" style="width: 50%;">
        <div class="mt-4"></div>
        <h5 class="text-center mb-2">Welcome Back</h5>
        <p class="text-center text-muted">Sign in to access your engagement schedules</p>

        <!-- Show inactivity alert -->
        <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
            <div class="alert alert-warning text-center" role="alert">
                You have been logged out due to inactivity. Please log in again.
            </div>
        <?php endif; ?>

        <!-- SSO Login Button -->
        <div class="d-grid mt-4 mb-3">
            <a href="../api/login.php" class="btn btn-primary" style="background-color: rgb(23,62,70); color: white; font-weight: 500; border: none !important;">
                <i class="bi bi-person-circle me-2"></i>Login with SSO
            </a>
        </div>

        <p class="text-center text-muted">Contact your administrator for account setup.</p>
        <p class="text-center text-muted" style="font-size: 10px;">This is a demo application. In production, users would be created by administrators.</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
