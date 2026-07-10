<div class="modal fade" id="companyHolidaysModal" tabindex="-1" aria-labelledby="companyHolidaysModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 680px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="eng-edit-hero ch-hero">
          <div>
            <div class="eng-edit-title" id="companyHolidaysModalTitle"><i class="bi bi-calendar2-week me-2"></i>Company Holidays</div>
            <p class="text-muted" style="font-size: 12.5px; margin: 4px 0 0;">Manage firm-wide holidays and closures</p>
          </div>
          <button type="button" id="chAddHolidayBtn" class="settings-action-btn">
            <i class="bi bi-plus-lg"></i> Add Holiday
          </button>
        </div>

        <div class="eng-edit-body">
          <input type="text" id="chSearchInput" class="eng-edit-input" placeholder="Search holidays..." style="margin-bottom: 14px;">

          <div id="chList">
            <div class="settings-empty-row">Loading...</div>
          </div>
        </div>

        <div class="eng-edit-footer">
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>
