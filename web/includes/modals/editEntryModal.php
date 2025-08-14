<div class="modal fade" id="editEntryModal" tabindex="-1" aria-labelledby="editEntryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editEntryModalLabel">Edit Entry</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <strong>Client:</strong> <span id="editClientName">—</span><br>
          <strong>User ID:</strong> <span id="editUserId">—</span><br>
          <strong>User Name:</strong> <span id="editUserName">—</span><br>
          <strong>Week Start:</strong> <span id="editWeekStart">—</span>
        </div>

        <input type="hidden" id="editEntryId" name="entry_id">
        <div class="mb-3">
          <label for="editAssignedHours" class="form-label">Assigned Hours</label>
          <input type="number" class="form-control" id="editAssignedHours" name="assigned_hours" required>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </div>
  </div>
</div>
