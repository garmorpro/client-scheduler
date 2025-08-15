<!-- 
<div class="modal fade" id="addEntryModal" tabindex="-1" aria-labelledby="addEntryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 rounded-3 shadow-sm">
      <form id="addEntryForm" action="add_entry.php" method="POST">

        <div class="modal-header bg-dark text-white py-2">
          <h5 class="modal-title fw-bold" id="addEntryModalLabel">
            <i class="bi bi-calendar-range me-2"></i> New Entry
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>


        <div class="modal-body">

          <div class="p-3 bg-light rounded border mb-4">
            <div class="row">
              <div class="col-md-4">
                <small class="text-muted">Employee Name</small>
                <div class="fw-semibold" id="addEntryEmployeeNameDisplay">--</div>
              </div>
              <div class="col-md-4">
                <small class="text-muted">Week Start</small>
                <div class="fw-semibold" id="addEntryWeekDisplay">--</div>
              </div>
            </div>
          </div>

  
          <input type="hidden" id="addEntryUserId" name="user_id" value="">
          <input type="hidden" id="addEntryWeek" name="week_start" value="">


          <div id="entryTypePrompt" class="text-center">
            <p>Please choose the type of entry:</p>
            <button type="button" class="btn badge text-white p-2 text-decoration-none fw-medium btn-dark" style="font-size: 14px !important;" id="btnTimeOffEntry">Time Off Entry</button>
            <button type="button" class="btn badge text-white p-2 text-decoration-none fw-medium btn-dark" style="font-size: 14px !important;" id="btnNewEntry">New Assignment Entry</button>
          </div>


          <div id="timeOffEntryContent" class="d-none">
            <div class="mb-3">
              <label for="timeOffHours" class="form-label">Hours</label>
              <input type="number" class="form-control" id="timeOffHours" name="time_off_hours" min="0" step="0.25" required>
            </div>
          </div>


          <div id="newEntryContent" class="d-none">

            <div class="mb-3 custom-dropdown">
              <label for="engagementInput" class="form-label">Client Name</label>
              <div class="dropdown-btn" id="dropdownBtn" tabindex="0" role="combobox" aria-expanded="false" aria-labelledby="selectedClient">
                <span id="selectedClient" class="text-muted">Select a client</span>
                <span>&#9662;</span>
              </div>
              <div class="dropdown-list" id="dropdownList" role="listbox" tabindex="-1" aria-labelledby="selectedClient" style="display: none;">
                <?php
                  $statusDisplayMap = ['confirmed'=>'Confirmed','pending'=>'Pending','not_confirmed'=>'Not Confirmed'];
                  $statusClassMap = ['confirmed'=>'text-confirmed','pending'=>'text-pending','not_confirmed'=>'text-not-confirmed'];
                ?>
                <?php foreach($clientsWithHours as $client): ?>
                  <?php
                    $statusKey = strtolower($client['status']);
                    $statusText = $statusDisplayMap[$statusKey] ?? ucfirst($statusKey);
                    $statusClass = $statusClassMap[$statusKey] ?? 'badge-default';
                  ?>
                  <div class="dropdown-item" data-engagement-id="<?php echo htmlspecialchars($client['engagement_id']); ?>" data-client-name="<?php echo htmlspecialchars($client['client_name']); ?>" role="option" tabindex="0">
                    <div>
                      <span class="fw-semibold"><?php echo htmlspecialchars($client['client_name']); ?></span><br>
                      <small class="text-muted">
                        <span class="text-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                        <i class="bi bi-dot"></i>
                        <?php echo number_format($client['assigned_hours'],2); ?> / <?php echo number_format($client['total_available_hours'],2); ?> hrs
                      </small>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              <input type="hidden" id="engagementInput" name="engagement_id">
            </div>

 
            <div class="mb-3">
              <label for="assignedHours" class="form-label">Hours</label>
              <input type="number" class="form-control" id="assignedHours" name="assigned_hours" min="0" step="0.25" required>
            </div>
          </div>
        </div>


        <div id="modal-footer" class="modal-footer d-none">
          <button type="button" class="btn btn-light fw-medium" style="font-size: 14px !important;" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark fw-medium" style="font-size: 14px !important;">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div> -->
