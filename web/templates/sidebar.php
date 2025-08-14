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


    <script>
        document.addEventListener('DOMContentLoaded', () => {
          const viewUserModal = document.getElementById('viewUserModal');
        
          viewUserModal.addEventListener('show.bs.modal', async (event) => {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            if (!userId) return;
        
            try {
              const response = await fetch(`get_user.php?user_id=${encodeURIComponent(userId)}`);
                  if (!response.ok) throw new Error('Network response was not ok');
            
                  const user = await response.json();
            
              function setText(id, text) {
                const el = document.getElementById(id);
                if (!el) {
                  console.warn(`Element with ID "${id}" not found.`);
                  return;
                }
                el.textContent = (text && text.toString().trim()) ? text : '-';
                }
          
              function formatDate(dateString) {
                if (!dateString) return '-';
                const d = new Date(dateString);
                if (isNaN(d)) return '-';
                const month = d.getMonth() + 1;
                const day = d.getDate();
                const year = d.getFullYear();
                return `${month}/${day}/${year}`;
                }
          
              function timeSince(dateString) {
          if (!dateString) return '-';
          const now = new Date();
              const past = new Date(dateString);
            
              if (isNaN(past.getTime())) return '-';  // invalid date
            
              let seconds = Math.floor((now - past) / 1000);
            
              if (seconds < 0) seconds = 0;  // if future date, treat as now
            
          if (seconds < 5) return 'just now';
              if (seconds < 60) return `${seconds}s ago`;
            
          const minutes = Math.floor(seconds / 60);
              if (minutes < 60) return `${minutes}m ago`;
            
          const hours = Math.floor(minutes / 60);
              if (hours < 24) return `${hours}h ago`;
            
          const days = Math.floor(hours / 24);
              if (days < 7) return `${days}d ago`;
            
          // fallback: show formatted date
          return formatDate(dateString);
        }


              const firstInitial = user.first_name ? user.first_name.charAt(0).toUpperCase() : '-';
              const lastInitial = user.last_name ? user.last_name.charAt(0).toUpperCase() : '-';
              setText('view_user_initials', firstInitial + lastInitial);

              setText('view_user_fullname', `${user.first_name || '-'} ${user.last_name || '-'}`);
              setText('view_email', user.email);
              setText('view_user_role', user.role);

              setText('view_first_name_detail', user.first_name);
              setText('view_last_name_detail', user.last_name);
              setText('view_email_detail', user.email);

              setText('view_status', user.status);
              setText('view_acct_status', user.status);
              setText('view_acct_created', formatDate(user.created));
              setText('view_acct_last_active', formatDate(user.last_active));

              function getAccessLevel(role) {
                switch(role.toLowerCase()) {
                  case 'admin': return 'Full Access';
                  case 'manager': return 'High Access';
                  case 'senior': return 'Restricted Access';
                  case 'staff': return 'Restricted Access';
                  case 'intern': return 'Restricted Access';
                  default: return 'Unknown Access';
                }
                }
          
              setText('view_acct_role', user.role);
                setText('view_acct_access_level', getAccessLevel(user.role || ''));
          
              function boolToEnabledDisabled(value) {
                return value == 1 ? 'Enabled' : 'Disabled';
                }
          
              const mfaEl = document.getElementById('view_acct_mfa');
              if (mfaEl) {
                const statusText = boolToEnabledDisabled(user.mfa_enabled);
                mfaEl.textContent = statusText;
                mfaEl.classList.remove('text-success', 'text-danger');
                if (statusText === 'Enabled') {
                  mfaEl.classList.add('text-success');
                } else {
                  mfaEl.classList.add('text-danger');
                }
                }
          
              const activityList = document.getElementById('view_recent_activity');
                if (activityList) {
                  activityList.innerHTML = ''; // clear previous
                
                  if (user.recent_activities && user.recent_activities.length > 0) {
                    user.recent_activities.forEach(act => {
                      const card = document.createElement('div');
                      card.className = 'activity-card';
                    
                      const desc = document.createElement('div');
                      desc.className = 'activity-description';
                      desc.title = act.description || '';
                      desc.textContent = act.description || '(no description)';
                    
                      const time = document.createElement('div');
                      time.className = 'activity-time';
                    
                      // Defensive parse of created_at
                      let createdAt = new Date(act.created_at);
                      if (isNaN(createdAt.getTime())) {
                        time.textContent = 'Invalid date';
                      } else {
                        time.textContent = timeSince(createdAt);
                      }
                  
                      card.appendChild(desc);
                      card.appendChild(time);
                      activityList.appendChild(card);
                    });
                  } else {
                    const empty = document.createElement('div');
                    empty.className = 'text-muted px-3';
                    empty.textContent = 'No recent activity found.';
                    activityList.appendChild(empty);
                  }
                } else {
                  console.warn("Element with id 'view_recent_activity' not found in DOM.");
                }

          
              // Update badge class for status
              const statusEl = document.getElementById('view_status');
              if (statusEl) {
                statusEl.classList.remove('active', 'inactive');
                if (user.status && user.status.toLowerCase() === 'active') {
                  statusEl.classList.add('active');
                } else {
                  statusEl.classList.add('inactive');
                }
                }
          
            } catch (error) {
              console.error('Failed to load user data:', error);
            }
          });
        });
    </script>
