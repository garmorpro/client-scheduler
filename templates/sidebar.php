<!-- sidebar.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isManager = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'manager';
$isServiceAccount = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'service_account';
?>

<div class="d-flex flex-column justify-content-between border-end fixed-top"
     style="width: 250px; height: 100vh; padding: 1.5rem; overflow-y: auto;">

    <!-- Branding -->
    <div>
        <div class="d-flex align-items-center mb-5">
            <div class="bg-dark text-white rounded p-2 me-2 d-flex align-items-center justify-content-center"
                 style="width: 40px; height: 40px;">
                <i class="bi bi-calendar2-week"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold">AARC-360</h5>
                <small class="text-muted">Schedule Manager</small>
            </div>
        </div>

        <!-- Nav Links -->
        <ul class="nav flex-column">
            <!-- <li class="nav-item mb-2">
                <a href="dashboard.php" class="nav-link d-flex align-items-center px-0 text-dark">
                    <i class="bi bi-layout-wtf me-2"></i>
                    Dashboard
                </a>
            </li> -->
            <?php if (!$isServiceAccount): ?>
            <?php if ($isAdmin || $isManager): ?>
                <li class="nav-item mb-2">
                    <a href="admin-panel.php" class="nav-link d-flex align-items-center px-0 text-dark">
                        <i class="bi bi-shield me-2"></i>
                        Admin Panel
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-item mb-2 <?php if ($isAdmin || $isManager) echo 'd-none'; ?>">
                <a href="my-schedule.php" class="nav-link d-flex align-items-center px-0 text-dark">
                    <i class="bi bi-person me-2"></i>
                    My Schedule
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="master-schedule.php" class="nav-link d-flex align-items-center px-0 text-dark">
                    <i class="bi bi-calendar-range me-2"></i>
                    Master Schedule
                </a>
            </li>
            <?php if ($isAdmin || $isManager): ?>
                <li class="nav-item mb-2">
                    <a href="client-management.php" class="nav-link d-flex align-items-center px-0 text-dark">
                        <i class="bi bi-building-gear me-2"></i>
                        Clients
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="engagement-management.php" class="nav-link d-flex align-items-center px-0 text-dark">
                        <i class="bi bi-file-earmark-text me-2"></i>
                        Engagements
                    </a>
                </li>
            <?php endif; ?>
            <?php else: ?>
                <li class="nav-item mb-2">
                    <a href="service-settings.php" class="nav-link d-flex align-items-center px-0 text-dark">
                        <i class="bi bi-shield me-2"></i>
                        Service Dashboard
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <?php
    // Assume you fetched from DB: $user_theme = 'light' or 'dark'
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = $user_theme ?? 'light';
}

$themeClass = $_SESSION['theme'] === 'dark' ? 'dark-mode' : '';

?>

<!-- Bootstrap icon -->
    <i id="themeToggle" class="bi theme-icon <?= $_SESSION['theme'] === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill' ?>"></i>

    <!-- Bottom User Info -->
    <div class="d-flex align-items-center mt-4">
        <div data-bs-toggle="modal" data-bs-target="#viewProfileModal" data-user-id="<?php echo $_SESSION['user_id']; ?>" class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-2"
             style="cursor: pointer; width: 36px; height: 36px;">
            <?php
            
            $fullName = $_SESSION['full_name'] ?? ''; // get full name from session
            $initials = '';
                        
            // Split the full name by spaces
            $nameParts = explode(' ', $fullName);
                        
            // Take the first character of the first two parts (first and last names)
            if (isset($nameParts[0])) {
                $initials .= strtoupper($nameParts[0][0]);
            }
            if (isset($nameParts[1])) {
                $initials .= strtoupper($nameParts[1][0]);
            }
            
            // Output the initials
            echo $initials;
            ?>

        </div>
        <div>
            <div class="fw-semibold"><?php echo $_SESSION['full_name']; ?></div>
            <small class="text-muted text-capitalize"><?php echo $_SESSION['user_role']; ?></small>
        </div>
        <!-- <a href="logout.php" class="ms-auto text-decoration-none text-muted">
            <i class="bi bi-box-arrow-right"></i>
        </a> -->
        <a href="/auth/logout.php" class="text-decoration-none text-muted d-flex align-items-center justify-content-end mt-2" style="padding-left: 30px;">
            <i class="bi bi-box-arrow-right"></i>
        </a>    
    </div>


</div>
