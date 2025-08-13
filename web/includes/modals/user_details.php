<div class="modal fade" id="employeeDetailsModal" tabindex="-1" aria-labelledby="employeeDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="employeeDetailsModalLabel">Employee Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <!-- Employee Role -->
          <h4 id="employeeName" class="text-center mb-3 fw-bold"></h4>
          <p id="employeeRole" class="text-muted mb-3"></p>

          <!-- Assigned Hours and Entries -->
          <div class="mb-4">
            <h6>Total Assigned Hours:</h6>
            <div class="d-flex justify-content-between">
              <span id="totalAssignedHoursEmployee" class="fw-bold fs-5 text-dark"></span>
              <span id="totalAvailableHoursEmployee" class="text-muted">/ <span id="totalAvailableHoursEmployeeVal">1000</span> hrs</span>
            </div>
            <div class="progress mt-2" style="height: 20px; border-radius: 10px;">
              <div id="utilizationBarEmployee" class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: 0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="1000"></div>
            </div>
          </div>

          <!-- Assigned Entries Section -->
          <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0">Upcoming Entries</h6>
            </div>
            <div class="card-body">
              <div id="assignedEntries" class="list-group"></div>
            </div>
          </div>

          <!-- Notes Section (Optional) -->
          <!-- <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0">Employee Notes</h6>
            </div>
            <div class="card-body">
              <p id="employeeNotes" class="text-muted">No notes available.</p>
            </div>
          </div> -->
        </div>
      </div>
    </div>
  </div>