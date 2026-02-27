<?php
// require_once '../includes/auth.php';
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
    <div class="card p-3 shadow" style="width: 100%; max-width: 425px;">
        <img src="../assets/images/aarc-360-logo-1.webp" alt="" class="mx-auto d-block" style="width: 50%;">
        <div class="mt-4"></div>
        <h5 class="text-center mb-2">Welcome Back</h5>
        <p class="text-center text-muted">Sign in to access your engagement schedules</p>

        <!-- Show error alert only if error is not empty -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="p-4" method="POST" action="">
            <div class="mb-3">
                <label for="account_name" class="form-label">Account name</label>
                <input type="text" class="form-control" name="account_name" placeholder="Enter your account name" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn" style="background-color: rgb(23,62, 70); color: white;">Sign In</button>
            </div>
        </form>

        <p class="text-center text-muted">Contact your administrator for account setup.</p>
        <p class="text-center text-muted" style="font-size: 10px;">This is a demo application. In production, users would be created by administrators.</p>
    </div>
</div>
</body>
</html>