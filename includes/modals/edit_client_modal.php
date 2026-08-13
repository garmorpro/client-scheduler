<!-- Edit Client Modal - restyled on the eng-edit-* look established for
     Add/Edit Engagement. -->
<div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
    <div class="modal-content">
      <form id="editClientForm">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-body">
            <div class="eng-edit-modal-title" id="editClientModalLabel">Edit Client</div>

            <input type="hidden" name="client_id" id="edit_modal_client_id">

            <div class="eng-edit-field">
              <label for="edit_client_name">Client Name</label>
              <input type="text" class="eng-edit-input" id="edit_client_name" name="client_name" required>
            </div>

            <div class="eng-edit-field">
              <label for="edit_onboarded_date">Onboarded Date</label>
              <input type="date" class="eng-edit-input" id="edit_onboarded_date" name="onboarded_date" required>
            </div>

            <div class="eng-edit-field">
              <label for="edit_status">Status</label>
              <select class="eng-edit-input" id="edit_status" name="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>

            <div class="eng-edit-field">
              <label for="edit_notes">Notes</label>
              <textarea class="eng-edit-input" id="edit_notes" name="notes" rows="2"></textarea>
            </div>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="eng-edit-btn-save">Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
