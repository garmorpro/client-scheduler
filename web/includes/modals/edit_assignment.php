<div class="modal fade" id="editAssignmentModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Assignment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="editAssignmentForm" action="update_entry.php" method="POST">
            <input type="hidden" id="editAssignmentId" name="assignment_id">
            <div class="mb-3">
              <label for="editAssignedHours" class="form-label">Assigned Hours</label>
              <input type="number" class="form-control" id="editAssignedHours" name="assigned_hours" min="0" required>
            </div>
            <div class="mb-3 text-end">
              <button type="submit" id="editSubmitBtn" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>