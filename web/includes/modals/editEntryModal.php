<div class="modal fade" id="editEntryModal" tabindex="-1" aria-labelledby="editEntryModalLabel" aria-hidden="true" inert>
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 rounded-3 shadow-sm">

      <!-- Header -->
      <div class="modal-header bg-dark text-white py-2">
        <h5 class="modal-title fw-bold" id="editEntryModalLabel">
          <i class="bi bi-pencil-square me-2"></i> Edit Entry
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">

        <!-- User & Client Details -->
        <div class="p-3 bg-light rounded border mb-4">
          <div class="row g-2">
            <div class="col-md-4">
              <small class="text-muted">Client</small>
              <div class="fw-semibold" id="editClientName">-</div>
            </div>
            <div class="col-md-4">
              <small class="text-muted">Employee Name</small>
              <div class="fw-semibold" id="editUserName">—</div>
            </div>
            <div class="col-md-4">
              <small class="text-muted">Week Start</small>
              <div class="fw-semibold" id="editWeekStart">—</div>
            </div>
          </div>
        </div>

        <form action="update_entry.php" method="POST">

        <!-- Entry Form -->
        <input type="hidden" id="editEntryId" name="entry_id">
        <div class="mb-3">
          <label for="editAssignedHours" class="form-label">Assigned Hours</label>
          <input type="number" class="form-control" id="editAssignedHours" name="assigned_hours" required>
        </div>

      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
      </form> 
    </div>
  </div>
</div>
