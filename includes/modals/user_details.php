<!-- Employee Details Modal - restyled on the eng-edit-* look established
     for Add/Edit Engagement. Note: as of this restyle, nothing in
     pages/master-schedule.php (or anywhere else) actually triggers
     #employeeDetailsModal - it's included but unreachable, an orphan same
     as a couple of others found this session. Left functionally as-is
     (just restyled) since wiring it up is a separate decision. -->
<div class="modal fade" id="employeeDetailsModal" tabindex="-1" aria-labelledby="employeeDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="eng-edit-body">
          <div class="eng-edit-modal-title" id="employeeDetailsModalLabel">Employee Details</div>
          <div id="employeeName" style="text-align:center; font-size:16px; font-weight:700; color:#16211f;"></div>
          <p id="employeeRole" style="text-align:center; font-size:12.5px; color:#6b7570; margin:2px 0 18px;"></p>

          <div class="eng-edit-field">
            <label>Total Assigned Hours</label>
            <div style="display:flex; justify-content:space-between; align-items:baseline;">
              <span id="totalAssignedHoursEmployee" style="font-size:18px; font-weight:700; color:#16211f;"></span>
              <span style="font-size:12.5px; color:#6b7570;">/ <span id="totalAvailableHoursEmployeeVal">1000</span> hrs</span>
            </div>
            <div class="eng-util-track" style="margin-top:8px;">
              <div id="utilizationBarEmployee" class="eng-util-fill green" style="width:0;"></div>
            </div>
          </div>

          <div class="eng-edit-field">
            <label>Upcoming Entries</label>
            <div id="assignedEntries" class="eng-vm-emp-list"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
