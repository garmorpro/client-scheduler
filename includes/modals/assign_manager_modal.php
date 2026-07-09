<div class="modal fade" id="assignManagerModal" tabindex="-1" aria-labelledby="assignManagerModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
    <div class="modal-content">
      <form id="assignManagerForm">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="assignManagerModalTitle">Assign Manager</div>
          </div>

          <div class="eng-edit-body">
            <p class="text-muted" style="font-size: 13px; margin-bottom: 10px;">
              This manager will review and approve time off requests for the following employees:
            </p>
            <div class="am-employee-chips" id="am_employee_chips"></div>

            <div class="eng-edit-field">
              <label for="am_manager_id">Manager</label>
              <select class="eng-edit-input" id="am_manager_id" name="manager_id" required>
                <option value="">Select a manager</option>
                <?php foreach ($availableManagers as $mgr): ?>
                  <option value="<?php echo $mgr['user_id']; ?>"><?php echo htmlspecialchars($mgr['full_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="eng-edit-btn-save">Assign Manager</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
