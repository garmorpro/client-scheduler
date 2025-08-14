<!-- sidebar.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isManager = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'manager';
?>

<div class="d-flex flex-column justify-content-between bg-light border-end fixed-top"
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
        </ul>
    </div>

    <!-- Bottom User Info -->
    <div class="d-flex align-items-center mt-4">
        <div id="sidebarUserInitials" class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-2"
             style="width: 36px; height: 36px;">
            <?php
            $firstInitial = isset($_SESSION['first_name'][0]) ? $_SESSION['first_name'][0] : '';
            $lastInitial = isset($_SESSION['last_name'][0]) ? $_SESSION['last_name'][0] : '';
            echo strtoupper($firstInitial . $lastInitial);
            ?>
        </div>
        <div>
            <div class="fw-semibold"><?php echo $_SESSION['first_name']; ?> <?php echo $_SESSION['last_name']; ?></div>
            <small class="text-muted text-capitalize"><?php echo $_SESSION['user_role']; ?></small>
        </div>
        <!-- <a href="logout.php" class="ms-auto text-decoration-none text-muted">
            <i class="bi bi-box-arrow-right"></i>
        </a> -->
        <a href="logout.php" class="text-decoration-none text-muted d-flex align-items-center justify-content-end mt-2" style="padding-left: 40px;">
            <i class="bi bi-box-arrow-right"></i>
        </a>    
    </div>
    

    <script>
document.addEventListener('DOMContentLoaded', () => {
    const initialsCircle = document.getElementById('sidebarUserInitials');
    if (!initialsCircle) return;

    initialsCircle.addEventListener('click', () => {
        // Populate modal fields
        document.getElementById('view_first_name').textContent = '<?php echo $_SESSION['first_name']; ?>';
        document.getElementById('view_user_fullname').textContent = '<?php echo $_SESSION['first_name'] . " " . $_SESSION['last_name']; ?>';
        document.getElementById('view_email').textContent = '<?php echo $_SESSION['email']; ?>';
        document.getElementById('view_user_role').textContent = '<?php echo $_SESSION['user_role']; ?>';
        document.getElementById('view_user_initials').textContent = '<?php echo strtoupper($firstInitial . $lastInitial); ?>';

        // Open modal
        const userModal = new bootstrap.Modal(document.getElementById('viewUserModal'));
        userModal.show();
    });
});
</script>


</div>



<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="min-width: 600px !important;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateUserModalLabel">
                <i class="bi bi-people"></i> User Details <br>
                <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important; padding-top: -10px !important;">Complete profile information for <span id="view_first_name"></span></span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">

            <div style="background-color: rgb(245,245,247); border-radius: 15px; display: flex; align-items: center; gap: 10px; padding: 10px; margin-top: -20px;">
              <div id="view_user_initials" 
                   class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                   style="padding: 25px !important; width: 50px; height: 50px; font-weight: 500; font-size: 20px;">
                <!-- Initials will go here -->
              </div>
              <div>
                <div id="view_user_fullname" class="fw-semibold"></div>
                <small id="view_email" class="text-muted"></small><br>
                <small class="text-capitalize badge-role mt-2" style="font-size: 12px;" id="view_user_role">...</small>
                <small class="text-capitalize badge-status mt-2" style="font-size: 12px;" id="view_status">...</small>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-6">
                <h6 class="mb-3">
                  <i class="bi bi-envelope"></i> Personal Information
                </h6>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">First Name:</strong>
                  <span id="view_first_name_detail" class="text-capitalize" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Last Name:</strong>
                  <span id="view_last_name_detail" class="text-capitalize" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Email:</strong>
                  <span id="view_email_detail" style="float: right;"></span>
                </p>
                <div class="mt-3"></div>
                <hr>
              </div>
              <div class="col-md-6">
                <h6 class="mb-3">
                  <i class="bi bi-person-lock"></i> Account Details
                </h6>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Created:</strong>
                  <span id="view_acct_created" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Last Active:</strong>
                  <span id="view_acct_last_active" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Status:</strong>
                  <span id="view_acct_status" class="text-capitalize" style="float: right;"></span>
                </p>
                <div class="mt-3"></div>
                <hr>
              </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-6">
                    <h6 class="mb-3">
                        <i class="bi bi-shield"></i> Access & Permissions
                    </h6>
                    <p class="text-muted mb-1" style="overflow: hidden;">
                        <strong style="float: left;">Role:</strong>
                        <span id="view_acct_role" class="text-capitalize" style="float: right;"></span>
                     </p>
                    <p class="text-muted mb-1" style="overflow: hidden;">
                        <strong style="float: left;">Access Level:</strong>
                        <span id="view_acct_access_level" class="text-capitalize" style="float: right;"></span>
                    </p>
                    <p class="text-muted mb-1" style="overflow: hidden;">
                        <strong style="float: left;">Two-Factor Auth:</strong>
                        <span id="view_acct_mfa" style="float: right;"></span>
                    </p>
                </div>
                <div class="col-md-6"></div>
            </div>

            <hr>

            <div class="col-md-12">
              <h6>Recent Activity</h6>
              <div id="view_recent_activity" style="max-height: 150px; overflow-y: auto;">
                <!-- Activities will be inserted here as cards -->
              </div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn text-muted" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
