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
      /* margin: 0 auto 1rem auto; */
    }
    .admin-icon svg {
      width: 24px;
      height: 24px;
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
    <p class="text-muted mb-">Welcome back, <?php echo $_SESSION['first_name']; ?>. As an administrator, you have access to the enhanced admin dashboard.

    <!-- <div class="p-5"></div> -->

    <div class="container">
    <div class="admin-card">
      <div class="admin-icon">
        <!-- Shield icon (Bootstrap Icon) -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3l7.5 3v6.75A9.75 9.75 0 0112 21a9.75 9.75 0 01-7.5-8.25V6L12 3z" />
        </svg>
      </div>
      <h5 class="fw-bold">Administrator Access</h5>
      <p class="text-muted mb-4">Access advanced system management, user administration, and detailed analytics.</p>
      <button class="admin-btn">Go to Admin Dashboard</button>
    </div>
  </div>

</p>


    <?php
    // Dump all session variables
echo '<pre>';  // Pre-format the output for better readability
var_dump($_SESSION);  // or use print_r($_SESSION) if you prefer
echo '</pre>';
?>

  </div>

</body>
</html>
