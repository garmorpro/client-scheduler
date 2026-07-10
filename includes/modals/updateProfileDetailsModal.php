<?php require_once __DIR__ . '/../csrf.php'; ?>
<div class="modal fade" id="updateProfileDetailsModal" tabindex="-1" aria-labelledby="updateProfileDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
    <div class="modal-content">
      <form id="updateProfileForm">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="updateProfileDetailsModalLabel"><i class="bi bi-pencil-square"></i> Edit Profile</div>
            <div class="text-muted" style="font-size: 12.5px; margin-top: 4px;">Update your name. Contact an administrator to change your email.</div>
          </div>

          <div class="eng-edit-body">
            <input type="hidden" id="update_user_id" name="user_id">

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="update_first_name">First Name</label>
                <input type="text" class="eng-edit-input" id="update_first_name" name="first_name" required>
              </div>
              <div class="eng-edit-field">
                <label for="update_last_name">Last Name</label>
                <input type="text" class="eng-edit-input" id="update_last_name" name="last_name" required>
              </div>
            </div>

            <div class="eng-edit-field">
              <label for="update_email">Email</label>
              <input type="email" class="eng-edit-input" id="update_email" disabled>
            </div>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="eng-edit-btn-save" id="updateProfileSaveBtn">Update Profile</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
