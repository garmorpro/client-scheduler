<div class="modal fade" id="engagementModal" tabindex="-1" aria-labelledby="engagementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="engagementForm" action="add_engagement.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="engagementModalLabel">Add Engagement</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">

            <div class="mb-3">
              <label for="client_name" class="form-label">Client Name</label>
              <input type="text" class="form-control" id="client_name" name="client_name" required>
            </div>

            <div class="mb-3">
              <label for="total_available_hours" class="form-label">Total Available Hours</label>
              <input type="number" step="0.1" min="0" class="form-control" id="total_available_hours" name="total_available_hours" required>
            </div>

            <div class="mb-3">
              <label for="status" class="form-label">Status</label>
              <select id="status" name="status" class="form-select" required>
                <option value="" disabled selected>Select status</option>
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
                <option value="not_confirmed">Not Confirmed</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="notes" class="form-label">Notes</label>
              <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Add any notes here..."></textarea>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Engagement</button>
          </div>
        </form>
      </div>
    </div>
  </div>