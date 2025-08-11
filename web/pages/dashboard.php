<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
      .admin-card {
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 2rem;
      text-align: center;
      background-color: #fff;
      box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .admin-icon {
      width: 50px;
      height: 50px;
      background-color: #f0f0f0;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem auto;
    }
    .admin-icon img {
      font-size: 28px;
      color: #2c2c2c;
    }
    .admin-btn {
      background-color: #0b0b16;
      color: #fff;
      border-radius: 8px;
      padding: 0.5rem 1.5rem;
      font-weight: 500;
      border: none;
    }
    .admin-btn:hover {
      background-color: #1a1a2e;
    }
    </style>

</head>
<body class="d-flex">

  <?php include_once '../templates/sidebar.php'; ?>

  <div class="flex-grow-1 p-4">
    <h3 class="mb-0">Dashboard</h3>
    <p class="text-muted mb-4">Welcome back, <?php echo $_SESSION['first_name']; ?>. As an administrator, you have access to the enhanced admin dashboard.</p>

    <div class="admin-card">
      <div class="admin-icon">
        <!-- Shield icon (Bootstrap Icon) -->
        <i class="bi bi-shield"></i>
      </div>
      <h5 class="fw-bold">Administrator Access</h5>
      <p class="text-muted mb-5">Access advanced system management, user administration, and detailed analytics.</p>
      <button class="admin-btn mt-2">Go to Admin Dashboard</button>
    </div>

  </div>

</body>
</html>
