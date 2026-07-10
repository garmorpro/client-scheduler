<div class="modal fade" id="policyModal" tabindex="-1" aria-labelledby="policyModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
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
              <label for="policy_doc_type">Document Type</label>
              <select class="eng-edit-input" id="policy_doc_type" name="doc_type">
                <option value="policy">Policy</option>
                <option value="memo">Memorandum</option>
              </select>
            </div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="policy_title" id="policy_title_label">Title</label>
                <input type="text" class="eng-edit-input" id="policy_title" name="title" placeholder="e.g. Remote Work Policy" required>
              </div>
              <div class="eng-edit-field" id="policy_effective_date_field">
                <label for="policy_effective_date">Effective Date</label>
                <input type="date" class="eng-edit-input" id="policy_effective_date" name="effective_date">
              </div>
            </div>

            <div class="eng-edit-row" id="policy_memo_fields" style="display:none;">
              <div class="eng-edit-field">
                <label for="policy_memo_to">To</label>
                <input type="text" class="eng-edit-input" id="policy_memo_to" name="memo_to" placeholder="e.g. AARC-360 Employees">
              </div>
              <div class="eng-edit-field">
                <label for="policy_memo_from">From</label>
                <input type="text" class="eng-edit-input" id="policy_memo_from" name="memo_from" placeholder="e.g. Tasha Darrah">
              </div>
            </div>

            <div class="eng-edit-field">
              <label>Content</label>
              <div id="policyQuillEditor"></div>
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
