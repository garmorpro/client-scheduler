<!-- sidebar.php -->
<div class="d-flex flex-column justify-content-between bg-light border-end" style="width: 250px; height: 100vh; padding: 1.5rem;">

  <!-- Branding -->
  <div>
    <div class="d-flex align-items-center mb-5">
      <div class="bg-dark text-white rounded p-2 me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
        <i class="bi bi-calendar2-week"></i> <!-- Bootstrap icon -->
      </div>
      <div>
        <h5 class="mb-0 fw-bold">AARC-360</h5>
        <small class="text-muted">Schedule Manager</small>
      </div>
    </div>

    <!-- Nav Links -->
    <ul class="nav flex-column">
      <li class="nav-item mb-2">
        <a href="dashboard.php" class="nav-link d-flex align-items-center px-0 text-dark">
          <i class="bi bi-speedometer2 me-2"></i>
          Dashboard
        </a>
      </li>
      <li class="nav-item mb-2">
        <a href="master-schedule.php" class="nav-link d-flex align-items-center px-0 text-dark active fw-semibold">
          <i class="bi bi-calendar3 me-2"></i>
          Master Schedule
        </a>
      </li>
      <li class="nav-item">
        <a href="my-schedule.php" class="nav-link d-flex align-items-center px-0 text-dark">
          <i class="bi bi-person-lines-fill me-2"></i>
          My Schedule
        </a>
      </li>
    </ul>
  </div>

  <!-- Bottom User Info -->
  <div class="d-flex align-items-center">
    <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
        <?php
        $firstInitial = isset($_SESSION['first_name'][0]) ? $_SESSION['first_name'][0] : '';
        $lastInitial = isset($_SESSION['last_name'][0]) ? $_SESSION['last_name'][0] : '';
        echo strtoupper($firstInitial . $lastInitial);
        ?>
    </div>
    <div>
      <div class="fw-semibold"><?php echo $_SESSION['first_name']; ?> <?php echo $_SESSION['last_name'] ?></div>
      <small class="text-muted text-capitalize"><?php echo $_SESSION['user_role']; ?></small>
    </div>
    <a href="logout.php" class="ms-auto text-decoration-none text-muted">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</div>
