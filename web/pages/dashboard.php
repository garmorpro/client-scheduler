<?php
session_start();

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

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
    .stat-card {
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 1.5rem;
      background: #fff;
      box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .stat-title {
      font-size: 0.9rem;
      color: #6c757d;
    }
    .stat-value {
      font-size: 1.5rem;
      font-weight: bold;
    }
    .tab-content {
      border: 1px solid #e0e0e0;
      border-top: none;
      border-radius: 0 0 12px 12px;
      background: #fff;
      padding: 1.5rem;
    }
    .nav-tabs .nav-link {
      border: none;
      border-bottom: 2px solid transparent;
      font-weight: 500;
      color: #6c757d;
    }
    .nav-tabs .nav-link.active {
      border-bottom: 2px solid #000;
      color: #000;
    }
    .status-badge {
      padding: 0.35em 0.65em;
      font-size: 0.75rem;
      border-radius: 0.5rem;
    }
    .status-active {
      background-color: #d1fae5;
      color: #065f46;
    }
    .status-inactive {
      background-color: #fef3c7;
      color: #92400e;
    }
    .activity-item {
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 1rem;
      margin-bottom: 1rem;
      background: #fff;
    }
    .activity-icon {
      font-size: 1.2rem;
      margin-right: 0.5rem;
    }
  </style>

</head>
<body class="d-flex">

  <?php include_once '../templates/sidebar.php'; ?>

  <div class="flex-grow-1 p-4">
    <h3 class="mb-0">Dashboard</h3>
    <p class="text-muted mb-4">Welcome back, <?php echo $_SESSION['first_name']; ?>. 
      <?php if ($isAdmin): ?>
      As an administrator, you have access to the enhanced admin dashboard.
      <?php else: ?>
      Here's an overview of your team's engagements.
      <?php endif; ?>
    </p>

    <?php if ($isAdmin): ?>
      <div class="admin-card">
        <div class="admin-icon">
          <!-- Shield icon (Bootstrap Icon) -->
          <i class="bi bi-shield"></i>
        </div>
        <h5 class="fw-bold">Administrator Access</h5>
        <p class="text-muted mb-5">Access advanced system management, user administration, and detailed analytics.</p>
        <button class="admin-btn mt-2">Go to Admin Dashboard</button>
      </div>
    <?php else: ?>
      Non-admin
    <?php endif; ?>

  </div>

</body>
</html>
