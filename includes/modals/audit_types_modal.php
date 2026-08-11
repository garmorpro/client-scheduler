<div class="modal fade" id="auditTypesModal" tabindex="-1" aria-labelledby="auditTypesModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 560px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="eng-edit-hero">
          <div class="eng-edit-title" id="auditTypesModalTitle"><i class="bi bi-clipboard2-check me-2"></i>Audit Types</div>
          <p class="text-muted" style="font-size: 12.5px; margin: 4px 0 0;">Manage the audit types engagements and staff assignments can be tagged with</p>
        </div>

        <div class="eng-edit-body">
          <form id="atAddForm" class="at-add-row">
            <input type="color" id="atNewColor" class="at-color-input" value="#4f8ef7" title="Color">
            <input type="text" id="atNewName" class="eng-edit-input" placeholder="New audit type name" required>
            <button type="submit" class="settings-action-btn"><i class="bi bi-plus-lg"></i> Add</button>
          </form>

          <div id="atList" class="at-list">
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
