<div class="modal fade" id="busySeasonModal" tabindex="-1" aria-labelledby="busySeasonModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
    <div class="modal-content">
      <form id="busySeasonForm">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="busySeasonModalTitle">Busy Season</div>
            <p class="text-muted" style="font-size: 12.5px; margin: 4px 0 0;">
              During this date range, the Master Schedule's overallocation warning allows 50 hours/week instead of the usual 40.
            </p>
          </div>

          <div class="eng-edit-body">
            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="bs_start_date">Start Date</label>
                <input type="date" class="eng-edit-input" id="bs_start_date" name="start_date"
                       value="<?php echo htmlspecialchars($busySeasonSettings['start_date'] ?? ''); ?>">
              </div>
              <div class="eng-edit-field">
                <label for="bs_end_date">End Date</label>
                <input type="date" class="eng-edit-input" id="bs_end_date" name="end_date"
                       value="<?php echo htmlspecialchars($busySeasonSettings['end_date'] ?? ''); ?>">
              </div>
            </div>
            <p class="text-muted" style="font-size: 11.5px; margin: 0;">
              Leave both blank and save to turn Busy Season off.
            </p>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="eng-edit-btn-save">Save Settings</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
