<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="min-width: 600px !important;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateUserModalLabel">
                <i class="bi bi-people"></i> User Details <br>
                <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important; padding-top: -10px !important;">Complete profile information for <span id="view_user_fullname_intro2"></span></span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">

            <div style="background-color: rgb(245,245,247); border-radius: 15px; display: flex; align-items: center; gap: 10px; padding: 10px; margin-top: -20px;">
              <div id="view_user_initials2" 
                   class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                   style="padding: 25px !important; width: 50px; height: 50px; font-weight: 500; font-size: 20px;">
                <!-- Initials will go here -->
              </div>
              <div>
                <div id="view_user_fullname2" class="fw-semibold"></div>
                <small id="view_email2" class="text-muted"></small><br>
                <small class="text-capitalize badge-role mt-2" style="font-size: 12px;" id="view_user_role2">...</small>
                <small class="text-capitalize badge-status mt-2" style="font-size: 12px;" id="view_status2">...</small>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-6">
                <h6 class="mb-3">
                  <i class="bi bi-envelope"></i> Personal Information
                </h6>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Full Name:</strong>
                  <span id="full_name2" class="text-capitalize" style="float: right;"></span>
                </p>
                <!-- <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Last Name:</strong>
                  <span id="view_last_name_detail2" class="text-capitalize" style="float: right;"></span>
                </p> -->
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Email:</strong>
                  <span id="view_email_detail2" style="float: right;"></span>
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
                  <span id="view_acct_created2" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Last Active:</strong>
                  <span id="view_acct_last_active2" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Status:</strong>
                  <span id="view_acct_status2" class="text-capitalize" style="float: right;"></span>
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
                        <span id="view_acct_role2" class="text-capitalize" style="float: right;"></span>
                     </p>
                    <p class="text-muted mb-1" style="overflow: hidden;">
                        <strong style="float: left;">Access Level:</strong>
                        <span id="view_acct_access_level2" class="text-capitalize" style="float: right;"></span>
                    </p>
                    <!-- <p class="text-muted mb-1" style="overflow: hidden;">
                        <strong style="float: left;">Two-Factor Auth:</strong>
                        <span id="view_acct_mfa2" style="float: right;"></span>
                    </p> -->
                </div>
                <div class="col-md-6"></div>
            </div>

            <hr>

            <div class="col-md-12">
              <h6>Recent Activity</h6>
              <div id="view_recent_activity2" style="max-height: 150px; overflow-y: auto;">
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