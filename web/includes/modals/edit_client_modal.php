<div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">
      <form id="editClientForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editClientModalLabel">Edit Client</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="client_id" id="edit_modal_client_id">

          <div class="mb-3">
            <label for="edit_client_name" class="form-label">Client Name</label>
            <input type="text" class="form-control" id="edit_client_name" name="client_name" required>
          </div>

          <div class="mb-3">
            <label for="edit_onboarded_date" class="form-label">Onboarded Date</label>
            <input type="date" class="form-control" id="edit_onboarded_date" name="onboarded_date" required>
          </div>

          <div class="mb-3">
            <label for="edit_status" class="form-label">Status</label>
            <select class="form-select" id="edit_status" name="status" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="edit_notes" class="form-label">Notes</label>
            <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
          </div>
        </div>

        <div class="modal-footer p-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>