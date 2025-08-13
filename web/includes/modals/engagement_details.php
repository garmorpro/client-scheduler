<div class="modal fade" id="clientDetailsModal" tabindex="-1" aria-labelledby="clientDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="clientDetailsModalLabel">Engagement Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
                  
        <div class="modal-body">
          <!-- Engagement Name -->
          <h3 id="clientName" class="text-center mb-3 fw-bold"></h3>

          <!-- Hidden ID for use in AJAX -->
          <input type="text" id="engagementId" value="">

          <!-- Engagement Status Editor (Inline Editable) -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Engagement Status</label>
            <div id="engagement-status-container">
              <span id="engagement-status-display" class="badge bg-warning text-dark" style="cursor: pointer;">Pending</span>
              <select id="engagement-status-select" class="form-select w-auto d-inline-block mt-2 d-none">
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
                <option value="not_confirmed">Not Confirmed</option>
              </select>
            </div>
          </div>

          <!-- Utilization Progress Bar -->
          <div class="mb-4">
            <h6>Total Assigned Hours</h6>
            <div class="d-flex justify-content-between">
              <span id="totalAssignedHours" class="fw-bold fs-5 text-dark"></span>
              <span id="totalHours" class="text-muted">/ <span id="totalAvailableHours">1000</span> hrs</span>
            </div>
            <div class="progress mt-2" style="height: 20px; border-radius: 10px;">
              <div id="utilizationBar" class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: 0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="1000"></div>
            </div>
          </div>

          <!-- Assigned Employees Section -->
          <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0">Assigned Consultants</h6>
            </div>
            <div class="card-body">
              <div id="assignedEmployees" class="list-group"></div>
            </div>
          </div>

          <!-- Notes Section -->
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0">Client Notes</h6>
            </div>
            <div class="card-body">
              <p id="clientNotes" class="text-muted">No notes available.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>