<div class="modal fade" id="holidayModal" tabindex="-1" aria-labelledby="holidayModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
    <div class="modal-content">
      <form id="holidayForm">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="holidayModalTitle">Add Holiday</div>
          </div>

          <div class="eng-edit-body">
            <input type="hidden" id="holiday_original_name">

            <div class="eng-edit-field">
              <label for="holiday_name">Holiday Name</label>
              <input type="text" class="eng-edit-input" id="holiday_name" placeholder="e.g. Labor Day" required>
            </div>

            <div class="hol-days-label">
              <span>Days Off</span>
              <button type="button" class="hol-add-day" id="holidayAddDayBtn">
                <i class="bi bi-plus"></i> Add Another Day
              </button>
            </div>
            <div id="holidayDaysContainer"></div>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="eng-edit-btn-save">Save Holiday</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
