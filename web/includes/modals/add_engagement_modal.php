<div class="modal fade" id="addEngagementModal" tabindex="-1" aria-labelledby="addEngagementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form id="addEngagementForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addEngagementModalLabel">Add Engagement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="client_id" id="modal_client_id">
          <input type="hidden" name="client_name" id="modal_client_name">
          <input type="hidden" name="year" id="modal_year" value="<?php echo date('Y'); ?>">

          <div class="mb-3">
            <label for="budget_hours" class="form-label">Budget Hours</label>
            <input type="number" min="0" class="form-control" id="budget_hours" name="budget_hours" required>
          </div>

          <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
              <option value="confirmed">Confirmed</option>
              <option value="pending">Pending</option>
              <option value="not_confirmed">Not Confirmed</option>
            </select>
          </div>
        </div>

        <div class="modal-footer p-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Add</button>
        </div>
      </form>
    </div>
  </div>
</div>