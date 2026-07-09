<div class="modal fade" id="directReportsModal" tabindex="-1" aria-labelledby="drModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="eng-edit-hero">
          <div class="eng-edit-title" id="drModalTitle">Direct Reports</div>
          <p class="text-muted" style="font-size: 12.5px; margin: 4px 0 0;">
            Check who reports to <strong id="drManagerName"></strong>.
          </p>
        </div>

        <div class="eng-edit-body" style="padding-bottom: 0;">
          <input type="text" class="eng-edit-input dr-search" id="drSearchInput" placeholder="Search employees...">
          <div class="dr-list" id="drList"></div>
        </div>

        <div class="eng-edit-footer">
          <span class="dr-selected-count" id="drSelectedCount"></span>
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="eng-edit-btn-save" id="drSaveBtn">Save</button>
        </div>
      </div>
    </div>
  </div>
</div>
