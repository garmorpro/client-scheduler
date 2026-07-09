<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="ud-hero">
          <div class="ud-header">
            <div class="ud-avatar" id="ud_avatar"></div>
            <div>
              <div class="ud-name" id="ud_name"></div>
              <div class="ud-email" id="ud_email"></div>
              <div class="ud-pills">
                <span class="role-pill text-capitalize" id="ud_role_pill"></span>
                <span class="status-pill" id="ud_status_pill"><span class="dot"></span><span id="ud_status_text"></span></span>
              </div>
            </div>
          </div>
          <div class="ud-tabs">
            <div class="ud-tab active" data-ud-tab="overview">
              <i class="bi bi-person"></i> Overview
            </div>
            <div class="ud-tab" data-ud-tab="engagements">
              <i class="bi bi-calendar3"></i> Engagements
              <span class="count-chip" id="ud_tab_eng_count">0</span>
            </div>
            <div class="ud-tab" data-ud-tab="timeoff">
              <i class="bi bi-airplane"></i> Time Off
              <span class="count-chip" id="ud_tab_timeoff_count">0</span>
            </div>
            <div class="ud-tab" data-ud-tab="activity">
              <i class="bi bi-clock-history"></i> Activity
            </div>
          </div>
        </div>

        <div class="ud-body">
          <!-- Overview -->
          <div class="ud-panel active" id="ud_panel_overview">
            <div class="stat-row">
              <div class="stat-card">
                <div class="stat-title">Engagements</div>
                <div class="stat-value" id="ud_stat_eng_count">0</div>
              </div>
              <div class="stat-card">
                <div class="stat-title">Total Hours</div>
                <div class="stat-value" id="ud_stat_hours">0</div>
              </div>
              <div class="stat-card">
                <div class="stat-title">Last Active</div>
                <div class="stat-value" id="ud_stat_last_active" style="font-size:12.5px;">-</div>
              </div>
            </div>

            <div class="detail-section-title">Personal Information</div>
            <div class="detail-row"><span class="detail-label">Full Name</span><span class="detail-value" id="ud_detail_fullname"></span></div>
            <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value" id="ud_detail_email"></span></div>
            <div class="detail-row"><span class="detail-label">Created</span><span class="detail-value" id="ud_detail_created"></span></div>

            <div class="detail-section-title">Access &amp; Permissions</div>
            <div class="detail-row"><span class="detail-label">Role</span><span class="detail-value text-capitalize" id="ud_detail_role"></span></div>
            <div class="detail-row"><span class="detail-label">Access Level</span><span class="detail-value" id="ud_detail_access_level"></span></div>
            <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value text-capitalize" id="ud_detail_status"></span></div>
            <div class="detail-row" id="ud_detail_manager_row" style="display:none;"><span class="detail-label">Manager</span><span class="detail-value" id="ud_detail_manager"></span></div>
          </div>

          <!-- Engagements -->
          <div class="ud-panel" id="ud_panel_engagements">
            <div class="eng-panel-header">
              <span class="eng-panel-hint" id="ud_eng_hint"></span>
              <button class="unassign-all-btn" id="ud_unassign_all_btn">
                <i class="bi bi-trash"></i> Unassign All
              </button>
            </div>
            <div class="eng-list" id="ud_eng_list"></div>
            <ul id="ud_eng_pagination" class="pagination pagination-sm justify-content-center mt-2" style="display:none;"></ul>
          </div>

          <!-- Time Off -->
          <div class="ud-panel" id="ud_panel_timeoff">
            <div class="eng-panel-header">
              <span class="eng-panel-hint" id="ud_timeoff_hint"></span>
              <span class="eng-panel-hint" id="ud_timeoff_click_hint">
                <i class="bi bi-cursor me-1"></i>Click a request for details
              </span>
            </div>
            <div class="eng-list" id="ud_timeoff_list"></div>
          </div>

          <!-- Activity -->
          <div class="ud-panel" id="ud_panel_activity">
            <div id="ud_activity_list"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="udTimeOffDetailModal" tabindex="-1" aria-labelledby="udtoTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="ud-hero" style="padding-bottom: 18px;">
          <div class="ud-header" style="margin-bottom: 0;">
            <div class="ud-avatar" id="udtoIcon"><i class="bi bi-airplane"></i></div>
            <div>
              <div class="ud-name" id="udtoTitle">Time Off Request</div>
              <div class="ud-pills" style="margin-top: 4px;">
                <span class="category-pill" id="udtoCategory"></span>
                <span class="eng-status-pill" id="udtoStatus"><span class="dot"></span><span id="udtoStatusText"></span></span>
              </div>
            </div>
          </div>
        </div>

        <div class="eng-edit-body">
          <div class="stat-row" style="grid-template-columns: repeat(2, 1fr);">
            <div class="stat-card">
              <div class="stat-title">Total Hours</div>
              <div class="stat-value" id="udtoTotalHours"></div>
            </div>
            <div class="stat-card">
              <div class="stat-title">Requested</div>
              <div class="stat-value" id="udtoRequested" style="font-size:13px;"></div>
            </div>
          </div>

          <div class="detail-section-title">Days</div>
          <div class="eng-vm-emp-list" id="udtoDaysList" style="margin-bottom:16px;"></div>

          <div id="udtoHistoryWrap" style="display:none;">
            <div class="detail-section-title">Notes</div>
            <div class="timeoff-comment-thread" id="udtoCommentHistory"></div>
          </div>
        </div>

        <div class="eng-edit-footer">
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>
