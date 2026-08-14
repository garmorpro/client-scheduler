<!-- Onboard Employee - replaces the old single-screen Add Employee modal
     with a short wizard. Step 1 (Basic Info) always shows; Step 2 (Manager)
     only applies to Staff/Senior (matching set_direct_reports.php's own
     role restriction on users.manager_id) and Step 3 (Training) only
     applies to Staff/Intern (matching add_user.php's auto-restriction
     seeding) - onboard_employee_modal.js computes which steps are actually
     relevant right after Basic Info based on the role picked, so nobody
     sees a step that doesn't apply to the role they just chose. -->
<div class="modal fade" id="onboardEmployeeModal" tabindex="-1" aria-labelledby="onboardEmployeeModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
    <div class="modal-content">
      <form id="onboardEmployeeForm">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="onboardEmployeeModalTitle">Onboard Employee</div>
            <div class="onboard-progress">
              <div class="onboard-progress-bar"><div class="onboard-progress-fill" id="onboardProgressFill"></div></div>
              <div class="onboard-progress-label" id="onboardProgressLabel">Step 1 of 4</div>
            </div>
          </div>

          <div class="eng-edit-body">
            <!-- Step: Basic Info -->
            <div class="onboard-step" data-step="basic">
              <div class="eng-edit-field">
                <label for="onboard_full_name">Full Name</label>
                <input type="text" class="eng-edit-input" id="onboard_full_name" name="full_name" required>
              </div>
              <div class="eng-edit-row">
                <div class="eng-edit-field">
                  <label for="onboard_email">Email</label>
                  <input type="email" class="eng-edit-input" id="onboard_email" name="email" required>
                </div>
                <div class="eng-edit-field">
                  <label for="onboard_role">Role</label>
                  <select class="eng-edit-input" id="onboard_role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="senior">Senior</option>
                    <option value="staff">Staff</option>
                    <option value="intern">Intern</option>
                    <option value="crm_team">CRM Team</option>
                  </select>
                </div>
              </div>
              <div class="eng-edit-field">
                <label for="onboard_job_title">Job Title</label>
                <input type="text" class="eng-edit-input" id="onboard_job_title" name="job_title" placeholder="e.g. Senior Consultant">
              </div>
              <p class="text-muted" style="font-size: 11.5px; margin-bottom: 0;">
                New accounts are created with a default password of <strong>change_me</strong> and will be prompted to set a real password on first login.
              </p>
            </div>

            <!-- Step: Manager Assignment (Staff/Senior only) -->
            <div class="onboard-step d-none" data-step="manager">
              <p class="text-muted" style="font-size: 12.5px; margin-top: 0;">Who does <strong id="onboardManagerStepName">this person</strong> report to?</p>
              <div class="eng-edit-field">
                <label for="onboard_manager_id">Manager <span class="eng-edit-optional">(optional)</span></label>
                <select class="eng-edit-input" id="onboard_manager_id" name="manager_id">
                  <option value="">No manager yet</option>
                  <?php
                  require_once '../includes/db.php';
                  $onboardMgrQuery = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'manager' ORDER BY full_name ASC");
                  while ($onboardMgrRow = $onboardMgrQuery->fetch_assoc()):
                  ?>
                  <option value="<?php echo (int) $onboardMgrRow['user_id']; ?>"><?php echo htmlspecialchars($onboardMgrRow['full_name']); ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
            </div>

            <!-- Step: Training Overview (Staff/Intern only) -->
            <div class="onboard-step d-none" data-step="training">
              <p class="text-muted" style="font-size: 12.5px; margin-top: 0;">
                <strong id="onboardTrainingStepName">This person</strong> starts restricted on every criterion below - remove any they're already trained on (e.g. transferring in with experience), or leave them all and clear each one from the Training page as they're tested and documented on it.
              </p>
              <div class="eng-edit-field">
                <div class="tr-editor-chips" id="onboardTrainingChips"></div>
              </div>
              <div class="eng-edit-field">
                <input type="text" class="eng-edit-input" id="onboardTrainingAddInput" placeholder="Type a criterion and press Enter to add it back">
              </div>
            </div>

            <!-- Step: Review -->
            <div class="onboard-step d-none" data-step="review">
              <div class="onboard-review-list" id="onboardReviewList"></div>
            </div>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" id="onboardBackBtn">Back</button>
            <button type="button" class="eng-edit-btn-save" id="onboardNextBtn">Next</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
