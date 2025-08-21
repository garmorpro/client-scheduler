<?php
// require_once '../includes/db.php';
// session_start();

// $error = '';

// if (!isset($_SESSION['force_user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $newPassword = trim($_POST['new_password']);
//     $confirmPassword = trim($_POST['confirm_password']);

//     if ($newPassword !== $confirmPassword) {
//         $error = "Passwords do not match.";
//     } elseif (strlen($newPassword) < 8) {
//         $error = "Password must be at least 8 characters.";
//     } else {
//         $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
//         $user_id = $_SESSION['force_user_id'];

//         $stmt = $conn->prepare("UPDATE users SET password = ?, change_password = 0 WHERE user_id = ?");
//         $stmt->bind_param("si", $hashed, $user_id);

//         if ($stmt->execute()) {
//             // Check if MFA is enabled
//             $stmt2 = $conn->prepare("SELECT mfa_enabled FROM users WHERE user_id = ?");
//             $stmt2->bind_param("i", $user_id);
//             $stmt2->execute();
//             $result = $stmt2->get_result();
//             $user = $result->fetch_assoc();
//             $stmt2->close();    

//             if ($user['mfa_enabled'] == 1) {
//                 // Generate MFA code
//                 $mfa_code = random_int(100000, 999999);
//                 $updateStmt = $conn->prepare("UPDATE users SET mfa_code = ? WHERE user_id = ?");
//                 $updateStmt->bind_param("ii", $mfa_code, $user_id);
//                 $updateStmt->execute();
//                 $updateStmt->close();

//                 // Set MFA session
//                 $_SESSION['mfa_user_id'] = $user_id;

//                 // Remove temporary force-login session
//                 unset($_SESSION['force_user_id'], $_SESSION['force_first_name'], $_SESSION['force_last_name'], $_SESSION['force_role'], $_SESSION['force_email']);

//                 header("Location: mfa.php");
//                 exit;
//             } else {
//                 // No MFA, complete login
//                 $_SESSION['user_id'] = $user_id;
//                 $_SESSION['first_name'] = $_SESSION['force_first_name'];
//                 $_SESSION['last_name'] = $_SESSION['force_last_name'];
//                 $_SESSION['user_role'] = $_SESSION['force_role'];
//                 $_SESSION['email'] = $_SESSION['force_email'];

//                 unset($_SESSION['force_user_id'], $_SESSION['force_first_name'], $_SESSION['force_last_name'], $_SESSION['force_role'], $_SESSION['force_email']);

//                 if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'manager') {
//                     header("Location: admin-panel.php");
//                 } else {
//                     header("Location: my-schedule.php");
//                 }
//                 exit;
//             }

//         } else {
//             $error = "Error updating password. Try again.";
//         }
//         $stmt->close();
//     }
// }
?>
<!--
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - Client Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: rgba(216, 216, 216, 1);">
<div class="container h-100 d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card p-3 shadow" style="width: 100%; max-width: 425px;">
        <img src="../assets/images/aarc-360-logo-1.webp" alt="" class="mx-auto d-block" style="width: 50%;">
        <div class="mt-4"></div>
        <h5 class="text-center mb-2">Change Your Password</h5>
        <p class="text-center text-danger mt-2">For security, please update your password before proceeding.</p>

        <?php //if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?php //echo htmlspecialchars($error); ?></div>
        <?php //endif; ?>

        <form class="p-4" method="POST" action="">
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" name="new_password" placeholder="Enter new password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm new password" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn" style="background-color: rgb(23,62,70); color: white;">Update Password</button>
            </div>
        </form>

        <p class="text-center text-muted mt-3">Contact your administrator if you experience any issues.</p>
        <p class="text-center text-muted" style="font-size: 10px;">This is a demo application.</p>
    </div>
</div>
</body>
</html> -->
