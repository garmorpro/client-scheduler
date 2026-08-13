<div class="offcanvas offcanvas-end client-vm-panel" tabindex="-1" id="viewClientModal" aria-labelledby="viewClientModalTitle" aria-hidden="true">
  <div class="offcanvas-body position-relative p-0">
    <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>

    <div class="ud-hero">
      <div class="ud-header">
        <div class="ud-avatar" id="vcAvatar"></div>
        <div>
          <div class="ud-name" id="vcTitle">Client Details</div>
          <div class="ud-email" id="vcOnboarded"></div>
          <div class="ud-pills">
            <span class="status-pill" id="vcStatusPill"><span class="dot"></span><span id="vcStatusText"></span></span>
          </div>
        </div>
      </div>
    </div>

    <div class="ud-body">
      <div class="stat-row" style="grid-template-columns: repeat(2, 1fr);">
        <div class="stat-card">
          <div class="stat-title">Total Engagements</div>
          <div class="stat-value" id="vcTotalEngagements">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-title">Confirmed Engagements</div>
          <div class="stat-value" id="vcConfirmedEngagements">0</div>
        </div>
      </div>

      <div class="detail-section-title">Engagement History</div>
      <div id="vcHistoryList">
        <div class="settings-empty-row">Loading...</div>
      </div>
    </div>

    <div class="eng-edit-footer">
      <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="offcanvas">Close</button>
    </div>
  </div>
</div>
