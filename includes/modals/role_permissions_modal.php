<div class="modal fade" id="rolePermissionsModal" tabindex="-1" aria-labelledby="rolePermissionsModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 1120px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="eng-edit-hero">
          <div class="eng-edit-title" id="rolePermissionsModalTitle">Role Permissions</div>
          <p class="text-muted" style="font-size: 12.5px; margin: 4px 0 0;">
            Choose what each role is allowed to do. "Manage" includes viewing; "View" is read-only. Admin always has full access.
          </p>
        </div>

        <div class="rp-body">
          <div class="rp-table-shell">
            <table class="rp-table">
              <thead>
                <tr class="rp-group-row">
                  <th class="rp-col-role"></th>
                  <th colspan="2"><div class="rp-col-head"><i class="bi bi-people-fill"></i>Employees</div></th>
                  <th colspan="2"><div class="rp-col-head"><i class="bi bi-briefcase-fill"></i>Clients &amp; Engagements</div></th>
                  <th colspan="2"><div class="rp-col-head"><i class="bi bi-calendar3"></i>Master<br>Schedule</div></th>
                  <th><div class="rp-col-head"><i class="bi bi-person-fill"></i>My<br>Schedule</div></th>
                  <th colspan="2"><div class="rp-col-head"><i class="bi bi-airplane-fill"></i>Time Off</div></th>
                  <th><div class="rp-col-head"><i class="bi bi-gear-fill"></i>System<br>Settings</div></th>
                </tr>
                <tr>
                  <th class="rp-col-role">Role</th>
                  <th class="rp-sub-head">View</th>
                  <th class="rp-sub-head">Manage</th>
                  <th class="rp-sub-head">View</th>
                  <th class="rp-sub-head">Manage</th>
                  <th class="rp-sub-head">View</th>
                  <th class="rp-sub-head">Manage</th>
                  <th class="rp-sub-head">View</th>
                  <th class="rp-sub-head">View</th>
                  <th class="rp-sub-head">Manage</th>
                  <th></th>
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
                  <td colspan="10" class="rp-full-access-cell">
                    <span class="rp-full-access">
                      <i class="bi bi-check-circle-fill"></i> Full access &mdash; not editable
                    </span>
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
