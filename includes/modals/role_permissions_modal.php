<div class="modal fade" id="rolePermissionsModal" tabindex="-1" aria-labelledby="rolePermissionsModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 640px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="eng-edit-hero">
          <div class="eng-edit-title" id="rolePermissionsModalTitle">Role Permissions</div>
          <p class="text-muted" style="font-size: 12.5px; margin: 4px 0 0;">
            Choose what each role is allowed to do. Admin always has full access.
          </p>
        </div>

        <div class="rp-body">
          <div class="rp-table-shell">
            <table class="rp-table">
              <thead>
                <tr>
                  <th>Role</th>
                  <th>Manage<br>Employees</th>
                  <th>Manage Clients<br>&amp; Engagements</th>
                  <th>Approve<br>Time Off</th>
                  <th>System<br>Settings</th>
                </tr>
              </thead>
              <tbody id="rpTableBody">
                <tr>
                  <td><span class="rp-role-name">Admin</span></td>
                  <td colspan="4" class="rp-full-access">Full access &mdash; not editable</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="eng-edit-footer">
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="eng-edit-btn-save" id="rpSaveBtn">Save Changes</button>
        </div>
      </div>
    </div>
  </div>
</div>
