<div class="modal fade" id="addEntryModal" tabindex="-1" aria-labelledby="addEntryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addEntryForm" action="add_entry.php" method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="addEntryModalLabel">
            <i class="bi bi-calendar-range me-2"></i>New Entry<br>
              <span class="text-muted" style="font-size: 12px; font-weight: 400; padding-top: 0;">
                Assign work for <strong><span id="addEntryEmployeeNameDisplay"></span></strong> during week of <strong><span id="addEntryWeek"></span></strong>
              </span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <!-- Hidden inputs -->
            <input type="hidden" id="addEntryUserId" name="user_id" value="">
            <input type="text" id="addEntryWeekDisplay" name="week_start" value="">

          

          <!-- Initial prompt with two buttons -->
          <div id="entryTypePrompt" class="text-center">
            <p>Please choose the type of entry:</p>
            <button type="button" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18);" id="btnTimeOffEntry">Time Off Entry</button>
            <button type="button" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18);" id="btnNewEntry">New Entry</button>
          </div>

          <!-- Time Off Entry content: only hours input -->
          <div id="timeOffEntryContent" class="d-none">
            <div class="mb-3">
              <label for="timeOffHours" class="form-label">Hours</label>
              <input type="number" class="form-control" id="timeOffHours" name="time_off_hours" min="0" step="0.25" required>
            </div>
          </div>

          <!-- New Entry content: client dropdown + hours input -->
          <div id="newEntryContent" class="d-none">

          
            <!-- Custom Client Dropdown -->
            <div class="mb-3 custom-dropdown">
              <label for="engagementInput" class="form-label">Client Name</label>
              <div
                class="dropdown-btn"
                id="dropdownBtn"
                tabindex="0"
                aria-haspopup="listbox"
                aria-expanded="false"
                role="combobox"
                aria-labelledby="selectedClient"
              >
                <span id="selectedClient" class="text-muted">Select a client</span>
                <span>&#9662;</span> <!-- Down arrow -->
              </div>

              <div
                class="dropdown-list"
                id="dropdownList"
                aria-expanded="true"
                role="listbox"
                tabindex="-1"
                aria-labelledby="selectedClient"
                style="display: block !important;"
              >
                <?php 
                  $statusDisplayMap = [
                    'confirmed' => 'Confirmed',
                    'pending' => 'Pending',
                    'not_confirmed' => 'Not Confirmed'
                  ];
                  $statusClassMap = [
                    'confirmed' => 'text-confirmed',
                    'pending' => 'text-pending',
                    'not_confirmed' => 'text-not-confirmed'
                  ];
                ?>
                <?php foreach ($clientsWithHours as $client): ?>
                  <?php
                    $statusKey = strtolower($client['status']);
                    $statusText = $statusDisplayMap[$statusKey] ?? ucfirst($statusKey);
                    $statusClass = $statusClassMap[$statusKey] ?? 'badge-default';
                  ?>
                  <div
                    class="dropdown-item"
                    data-engagement-id="<?php echo htmlspecialchars($client['engagement_id']); ?>"
                    data-client-name="<?php echo htmlspecialchars($client['client_name']); ?>"
                    role="option"
                    tabindex="0"
                  >
                    <div>
                      <span class="fw-semibold"><?php echo htmlspecialchars($client['client_name']); ?></span><br>
                      <small class="text-muted">
                        <span class="text-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                        <i class="bi bi-dot"></i> 
                        <?php echo number_format($client['assigned_hours'], 2); ?> / <?php echo number_format($client['total_available_hours'], 2); ?> hrs
                      </small>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              
              <!-- Hidden input to hold selected value for form submission -->
              <input type="hidden" id="engagementInput" name="engagement_id">
            </div>
              
            <!-- Assigned hours -->
            <div class="mb-3">
              <label for="assignedHours" class="form-label">Hours</label>
              <input type="number" class="form-control" id="assignedHours" name="assigned_hours" min="0" step="0.25" required>
            </div>
          </div>
        </div>

        <div id="modal-footer" class="modal-footer d-none">
          <button type="button" class="btn badge text-black p-2 text-decoration-none fw-medium" style="font-size: .875rem; box-shadow: inset 0 0 0 1px rgb(229,229,229);" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18); border:none !important;">Submit</button>
        </div>
      </form>
    </div>
  </div>
  </div>