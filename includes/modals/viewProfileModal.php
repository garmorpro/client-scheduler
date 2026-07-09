<div class="modal fade" id="viewProfileModal" tabindex="-1" aria-labelledby="viewProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="pd-hero">
          <div class="pd-header">
            <div class="pd-avatar" id="pd_avatar"></div>
            <div>
              <div class="pd-name" id="pd_name"></div>
              <div class="pd-email" id="pd_email"></div>
              <div class="pd-pills">
                <span class="pd-role-pill text-capitalize" id="pd_role_pill"></span>
                <span class="pd-status-pill" id="pd_status_pill"><span class="dot"></span><span id="pd_status_text"></span></span>
              </div>
            </div>
          </div>
        </div>

        <div class="pd-body">
          <div class="pd-section-title">Personal Information</div>
          <div class="pd-detail-row"><span class="pd-label">First Name</span><span class="pd-value text-capitalize" id="pd_first_name"></span></div>
          <div class="pd-detail-row"><span class="pd-label">Last Name</span><span class="pd-value text-capitalize" id="pd_last_name"></span></div>
          <div class="pd-detail-row"><span class="pd-label">Email</span><span class="pd-value" id="pd_email_detail"></span></div>

          <div class="pd-section-title">Account Details</div>
          <div class="pd-detail-row"><span class="pd-label">Created</span><span class="pd-value" id="pd_created"></span></div>
          <div class="pd-detail-row"><span class="pd-label">Last Active</span><span class="pd-value" id="pd_last_active"></span></div>
          <div class="pd-detail-row"><span class="pd-label">Status</span><span class="pd-value text-capitalize" id="pd_status_detail"></span></div>

          <div class="pd-section-title">Access &amp; Permissions</div>
          <div class="pd-detail-row"><span class="pd-label">Role</span><span class="pd-value text-capitalize" id="pd_role_detail"></span></div>
          <div class="pd-detail-row"><span class="pd-label">Access Level</span><span class="pd-value" id="pd_access_level"></span></div>

          <div class="pd-section-title">Recent Activity</div>
          <div id="pd_activity_list"></div>
        </div>

        <div class="pd-footer">
          <button type="button" class="pd-btn-edit" data-bs-toggle="modal" data-bs-target="#updateProfileDetailsModal" data-user-id="<?php echo $_SESSION['user_id']; ?>">
            <i class="bi bi-pencil-square"></i> Edit Profile
          </button>
          <button type="button" class="pd-btn-close" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>
