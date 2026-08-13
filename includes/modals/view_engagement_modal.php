<div class="offcanvas offcanvas-end eng-vm-panel" tabindex="-1" id="viewEngagementModal" aria-labelledby="viewEngagementModalTitle" aria-hidden="true">
  <div class="offcanvas-body position-relative p-0">
    <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    <div id="viewEngagementModalBody">
      <!-- content injected here -->
    </div>
  </div>
</div>

<div class="modal fade" id="viewEngDeleteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="eng-edit-hero">
          <div class="eng-edit-title">Delete Engagement</div>
        </div>
        <div class="eng-edit-body">
          <p style="font-size: 13px; color: #6b7570; margin-bottom: 14px;">This permanently deletes the engagement and cannot be undone. Archive it instead if you just want to keep it out of the active list.</p>
          <div class="eng-edit-field">
            <label for="viewEngDeleteConfirmInput">Type <strong>DELETE</strong> to confirm</label>
            <input type="text" id="viewEngDeleteConfirmInput" class="eng-edit-input" autocomplete="off" placeholder="DELETE">
          </div>
        </div>
        <div class="eng-edit-footer">
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="eng-edit-btn-save" id="viewEngDeleteConfirmBtn" style="background:#c0392b;" disabled>Delete Permanently</button>
        </div>
      </div>
    </div>
  </div>
</div>
