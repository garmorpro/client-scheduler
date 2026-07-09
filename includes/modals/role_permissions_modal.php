<div class="modal fade" id="rolePermissionsModal" tabindex="-1" aria-labelledby="rolePermissionsModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 720px;">
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
                  <th class="rp-col-role">Role</th>
                  <th>
                    <div class="rp-col-head"><i class="bi bi-people-fill"></i>Manage<br>Employees</div>
                  </th>
                  <th>
                    <div class="rp-col-head"><i class="bi bi-briefcase-fill"></i>Manage Clients<br>&amp; Engagements</div>
                  </th>
                  <th>
                    <div class="rp-col-head"><i class="bi bi-airplane-fill"></i>Approve<br>Time Off</div>
                  </th>
                  <th>
                    <div class="rp-col-head"><i class="bi bi-gear-fill"></i>System<br>Settings</div>
                  </th>
                </tr>
              </thead>
              <tbody id="rpTableBody">
                <tr class="rp-admin-row">
                  <td>
                    <span class="rp-role-cell">
                      <span class="rp-role-avatar admin"><i class="bi bi-shield-lock-fill"></i></span>
                      <span class="rp-role-name">Admin</span>
                    </span>
                  </td>
                  <td colspan="4" class="rp-full-access">
                    <i class="bi bi-check-circle-fill"></i> Full access &mdash; not editable
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="eng-edit-footer">
          <span class="rp-dirty-hint" id="rpDirtyHint">No changes yet</span>
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="eng-edit-btn-save" id="rpSaveBtn" disabled>Save Changes</button>
        </div>
      </div>
    </div>
  </div>
</div>
