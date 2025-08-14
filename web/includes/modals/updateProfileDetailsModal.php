<div class="modal fade" id="updateProfileDetailsModal" tabindex="-1" aria-labelledby="updateProfileDetailsModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="updateProfileForm" action="update_user.php" method="POST" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateProfileDetailsModalLabel">
                <i class="bi bi-pencil-square"></i> Edit User <br>
                <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important; padding-top: -10px !important;">Update user information and permissions</span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <div class="modal-body">

            <input type="hidden" id="update_user_id" name="user_id" required>

            <div class="mb-3">
              <label for="update_first_name" class="form-label">First Name</label>
              <input type="text" class="form-control" id="update_first_name" name="first_name" required>
            </div>

            <div class="mb-3">
              <label for="update_last_name" class="form-label">Last Name</label>
              <input type="text" class="form-control" id="update_last_name" name="last_name" required>
            </div>

            <div class="mb-3">
              <label for="update_email" class="form-label">Email</label>
              <input type="email" class="form-control" id="update_email" name="email" required>
            </div>

            <div class="mb-3">
              <label for="update_role" class="form-label">Role</label>
              <select class="form-select" id="update_role" name="role" required>
                <option value="" disabled>Select role</option>
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="senior">Senior</option>
                <option value="staff">Staff</option>
                <!-- Add more roles as needed -->
              </select>
            </div>

            <div class="mb-3">
              <label for="update_status" class="form-label">Status</label>
              <select class="form-select" id="update_status" name="status" required>
                <option value="" disabled>Select status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <!-- Add more statuses as needed -->
              </select>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn badge text-black p-2 text-decoration-none fw-medium" style="font-size: .875rem; border: 1px solid rgb(229,229,229);" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18);">Update User</button>
          </div>
        </form>
      </div>
    </div>