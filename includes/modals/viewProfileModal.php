<div class="modal fade" id="viewProfileModal" tabindex="-1" aria-labelledby="viewProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="ud-hero">
          <div class="ud-header">
            <div class="ud-avatar" id="pf_avatar"></div>
            <div>
              <div class="ud-name" id="pf_name"></div>
              <div class="ud-email" id="pf_email"></div>
              <div class="ud-pills">
                <span class="role-pill text-capitalize" id="pf_role_pill"></span>
                <span class="status-pill" id="pf_status_pill"><span class="dot"></span><span id="pf_status_text"></span></span>
              </div>
            </div>
          </div>
          <div class="ud-tabs">
            <div class="ud-tab active" data-pf-tab="overview">
              <i class="bi bi-person"></i> Overview
            </div>
            <div class="ud-tab" data-pf-tab="engagements">
              <i class="bi bi-calendar3"></i> Engagements
              <span class="count-chip" id="pf_tab_eng_count">0</span>
            </div>
            <div class="ud-tab" data-pf-tab="activity">
              <i class="bi bi-clock-history"></i> Activity
            </div>
          </div>
        </div>

        <div class="ud-body">
          <!-- Overview -->
          <div class="ud-panel active" id="pf_panel_overview">
            <div class="stat-row">
              <div class="stat-card">
                <div class="stat-title">Engagements</div>
                <div class="stat-value" id="pf_stat_eng_count">0</div>
              </div>
              <div class="stat-card">
                <div class="stat-title">Total Hours</div>
                <div class="stat-value" id="pf_stat_hours">0</div>
              </div>
              <div class="stat-card">
                <div class="stat-title">Last Active</div>
                <div class="stat-value" id="pf_stat_last_active" style="font-size:12.5px;">-</div>
              </div>
            </div>

            <div class="detail-section-title">Personal Information</div>
            <div class="detail-row"><span class="detail-label">First Name</span><span class="detail-value text-capitalize" id="pf_first_name"></span></div>
            <div class="detail-row"><span class="detail-label">Last Name</span><span class="detail-value text-capitalize" id="pf_last_name"></span></div>
            <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value" id="pf_email_detail"></span></div>

            <div class="detail-section-title">Account Details</div>
            <div class="detail-row"><span class="detail-label">Created</span><span class="detail-value" id="pf_created"></span></div>
            <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value text-capitalize" id="pf_status_detail"></span></div>

            <div class="detail-section-title">Access &amp; Permissions</div>
            <div class="detail-row"><span class="detail-label">Role</span><span class="detail-value text-capitalize" id="pf_role_detail"></span></div>
            <div class="detail-row"><span class="detail-label">Access Level</span><span class="detail-value" id="pf_access_level"></span></div>
          </div>

          <!-- Engagements -->
          <div class="ud-panel" id="pf_panel_engagements">
            <div class="eng-panel-header">
              <span class="eng-panel-hint" id="pf_eng_hint"></span>
              <?php if ($isAdmin ?? false): ?>
                <button class="unassign-all-btn" id="pf_unassign_all_btn">
                  <i class="bi bi-trash"></i> Unassign All
                </button>
              <?php endif; ?>
            </div>
            <div class="eng-list" id="pf_eng_list"></div>
            <ul id="pf_eng_pagination" class="pagination pagination-sm justify-content-center mt-2" style="display:none;"></ul>
          </div>

          <!-- Activity -->
          <div class="ud-panel" id="pf_panel_activity">
            <div id="pf_activity_list"></div>
          </div>
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
