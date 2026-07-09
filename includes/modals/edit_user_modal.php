<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
    <div class="modal-content">
      <form id="editUserForm">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="editUserModalTitle">Edit Employee</div>
          </div>

          <div class="eng-edit-body">
            <input type="hidden" id="edit_user_id" name="user_id">

            <div class="eng-edit-field">
              <label for="edit_user_full_name">Full Name</label>
              <input type="text" class="eng-edit-input" id="edit_user_full_name" name="full_name" required>
            </div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="edit_user_email">Email</label>
                <input type="email" class="eng-edit-input" id="edit_user_email" name="email" required>
              </div>
              <div class="eng-edit-field">
                <label for="edit_user_role">Role</label>
                <select class="eng-edit-input" id="edit_user_role" name="role" required>
                  <option value="admin">Admin</option>
                  <option value="manager">Manager</option>
                  <option value="senior">Senior</option>
                  <option value="staff">Staff</option>
                  <option value="intern">Intern</option>
                  <option value="crm_team">CRM Team</option>
                </select>
              </div>
            </div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="edit_user_job_title">Job Title</label>
                <input type="text" class="eng-edit-input" id="edit_user_job_title" name="job_title" placeholder="e.g. Senior Consultant">
              </div>
              <div class="eng-edit-field">
                <label for="edit_user_status">Status</label>
                <select class="eng-edit-input" id="edit_user_status" name="status" required>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="eng-edit-btn-save">Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
