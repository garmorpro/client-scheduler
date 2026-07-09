<div class="modal fade" id="rolePermissionsModal" tabindex="-1" aria-labelledby="rolePermissionsModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 680px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="eng-edit-hero">
          <div class="eng-edit-title" id="rolePermissionsModalTitle">Role Permissions</div>
        </div>

        <div class="rp-body">
          <div class="rp-grid">

            <div class="rp-card">
              <div class="rp-card-header">
                <span class="rp-role-name">Admin</span>
                <span class="rp-access-pill full">Full Access</span>
              </div>
              <ul class="rp-list">
                <li class="yes"><i class="bi bi-check-lg"></i>Manage user accounts &amp; roles</li>
                <li class="yes"><i class="bi bi-check-lg"></i>Configure system settings</li>
                <li class="yes"><i class="bi bi-check-lg"></i>Add, edit &amp; archive clients/engagements</li>
                <li class="yes"><i class="bi bi-check-lg"></i>Edit Master Schedule assignments</li>
                <li class="yes"><i class="bi bi-check-lg"></i>Manage company holidays</li>
              </ul>
            </div>

            <div class="rp-card">
              <div class="rp-card-header">
                <span class="rp-role-name">Manager</span>
                <span class="rp-access-pill edit">Edit Access</span>
              </div>
              <ul class="rp-list">
                <li class="no"><i class="bi bi-dash-lg"></i>Manage user accounts &amp; roles</li>
                <li class="no"><i class="bi bi-dash-lg"></i>Configure system settings</li>
                <li class="yes"><i class="bi bi-check-lg"></i>Add, edit &amp; archive clients/engagements</li>
                <li class="no"><i class="bi bi-dash-lg"></i>Edit Master Schedule assignments</li>
                <li class="yes"><i class="bi bi-check-lg"></i>Manage company holidays</li>
              </ul>
            </div>

            <div class="rp-card">
              <div class="rp-card-header">
                <span class="rp-role-name">Senior</span>
                <span class="rp-access-pill view">View Only</span>
              </div>
              <ul class="rp-list">
                <li class="no"><i class="bi bi-dash-lg"></i>Manage clients, engagements &amp; users</li>
                <li class="yes"><i class="bi bi-check-lg"></i>View Master Schedule</li>
                <li class="yes"><i class="bi bi-check-lg"></i>View own schedule &amp; assignments</li>
              </ul>
            </div>

            <div class="rp-card">
              <div class="rp-card-header">
                <span class="rp-role-name">Staff</span>
                <span class="rp-access-pill view">View Only</span>
              </div>
              <ul class="rp-list">
                <li class="no"><i class="bi bi-dash-lg"></i>Manage clients, engagements &amp; users</li>
                <li class="yes"><i class="bi bi-check-lg"></i>View Master Schedule</li>
                <li class="yes"><i class="bi bi-check-lg"></i>View own schedule &amp; assignments</li>
              </ul>
            </div>

          </div>
        </div>

        <div class="eng-edit-footer">
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>
