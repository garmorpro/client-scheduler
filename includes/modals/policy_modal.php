<div class="modal fade" id="policyModal" tabindex="-1" aria-labelledby="policyModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
    <div class="modal-content">
      <form id="policyForm">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="policyModalTitle">New Policy</div>
          </div>

          <div class="eng-edit-body">
            <input type="hidden" id="policy_id" name="policy_id">

            <div class="eng-edit-field">
              <label for="policy_title">Title</label>
              <input type="text" class="eng-edit-input" id="policy_title" name="title" placeholder="e.g. Remote Work Policy" required>
            </div>

            <div class="eng-edit-field">
              <label for="policy_pdf_file">PDF File</label>
              <div class="policy-pdf-current" id="policyPdfCurrent" style="display:none;">
                <i class="bi bi-file-earmark-pdf"></i> <span id="policyPdfCurrentName"></span>
                <span class="policy-pdf-replace-hint">Choose a new file below to replace it</span>
              </div>
              <input type="file" class="eng-edit-input" id="policy_pdf_file" name="pdf_file" accept="application/pdf">
            </div>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="eng-edit-btn-save" id="policySaveBtn">Save Policy</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
