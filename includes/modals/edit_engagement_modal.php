<div class="modal fade" id="editEngagementModal" tabindex="-1" aria-labelledby="editEngagementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form id="editEngagementForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editEngagementModalLabel">Edit Engagement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="engagement_id" id="edit_eng_engagement_id">

          <div class="mb-3">
            <label class="form-label">Client</label>
            <input type="text" class="form-control" id="edit_eng_client_name" disabled>
          </div>

          <div class="mb-3">
            <label for="edit_eng_budgeted_hours" class="form-label">Budgeted Hours</label>
            <input type="number" min="0" class="form-control" id="edit_eng_budgeted_hours" name="budgeted_hours" required>
          </div>

          <div class="mb-3">
            <label for="edit_eng_status" class="form-label">Status</label>
            <select class="form-select" id="edit_eng_status" name="status" required>
              <option value="confirmed">Confirmed</option>
              <option value="pending">Pending</option>
              <option value="not_confirmed">Not Confirmed</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="edit_eng_manager" class="form-label">Manager</label>
            <select class="form-select" id="edit_eng_manager" name="manager" required>
              <option value="">Select Manager</option>
              <?php
              require '../includes/db.php';
              $managerQuery = $conn->query("SELECT full_name FROM users WHERE role='manager' ORDER BY full_name ASC");
              while ($row = $managerQuery->fetch_assoc()) {
                  echo '<option value="' . htmlspecialchars($row['full_name']) . '">' . htmlspecialchars($row['full_name']) . '</option>';
              }
              ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="edit_eng_notes" class="form-label">Notes</label>
            <textarea class="form-control" id="edit_eng_notes" name="notes" rows="2"></textarea>
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
