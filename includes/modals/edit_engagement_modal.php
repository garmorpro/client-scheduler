<div class="modal fade" id="editEngagementModal" tabindex="-1" aria-labelledby="editEngagementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
    <div class="modal-content">
      <form id="editEngagementForm">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="editEngagementModalLabel">Edit Engagement</div>
          </div>

          <div class="eng-edit-body">
            <input type="hidden" name="engagement_id" id="edit_eng_engagement_id">

            <div class="eng-edit-field">
              <label>Client</label>
              <input type="text" class="eng-edit-input" id="edit_eng_client_name" disabled>
            </div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="edit_eng_budgeted_hours">Budgeted Hours</label>
                <input type="number" min="0" class="eng-edit-input" id="edit_eng_budgeted_hours" name="budgeted_hours" required>
              </div>

              <div class="eng-edit-field">
                <label for="edit_eng_status">Status</label>
                <select class="eng-edit-input" id="edit_eng_status" name="status" required>
                  <option value="confirmed">Confirmed</option>
                  <option value="pending">Pending</option>
                  <option value="not_confirmed">Not Confirmed</option>
                </select>
              </div>
            </div>

            <div class="eng-edit-field">
              <label for="edit_eng_manager">Manager</label>
              <select class="eng-edit-input" id="edit_eng_manager" name="manager" required>
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

            <div class="eng-edit-field">
              <label for="edit_eng_notes">Notes</label>
              <textarea class="eng-edit-input" id="edit_eng_notes" name="notes" rows="3"></textarea>
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
