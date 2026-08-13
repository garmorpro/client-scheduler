<div class="modal fade" id="editEngagementModal" tabindex="-1" aria-labelledby="editEngagementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
    <div class="modal-content">
      <form id="editEngagementForm">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="editEngagementModalLabel">Edit Engagement</div>
          </div>

          <div class="eng-edit-body">
            <input type="hidden" name="engagement_id" id="edit_eng_engagement_id">

            <div class="eng-edit-field">
              <label>Client</label>
              <input type="text" class="eng-edit-input" id="edit_eng_client_name" disabled>
            </div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="edit_eng_location">Location</label>
                <input type="text" class="eng-edit-input" id="edit_eng_location" name="location" placeholder="e.g. Charlottesville, VA">
              </div>

              <div class="eng-edit-field">
                <label for="edit_eng_poc">Point of Contact</label>
                <input type="text" class="eng-edit-input" id="edit_eng_poc" name="poc" placeholder="Client-side contact name">
              </div>
            </div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="edit_eng_budgeted_hours">Budgeted Hours</label>
                <input type="number" min="0" class="eng-edit-input" id="edit_eng_budgeted_hours" name="budgeted_hours" required>
              </div>

              <div class="eng-edit-field">
                <label for="edit_eng_status">Status</label>
                <select class="eng-edit-input" id="edit_eng_status" name="status" required>
                  <option value="confirmed">Confirmed</option>
                  <option value="pending">Pending</option>
                  <option value="not_confirmed">Not Confirmed</option>
                </select>
              </div>
            </div>

            <div class="eng-edit-field">
              <label for="edit_eng_manager">Manager</label>
              <select class="eng-edit-input" id="edit_eng_manager" name="manager" required>
                <option value="">Select Manager</option>
                <?php
                require '../includes/db.php';
                $managerQuery = $conn->query("SELECT full_name FROM users WHERE role='manager' ORDER BY full_name ASC");
                while ($row = $managerQuery->fetch_assoc()) {
                    echo '<option value="' . htmlspecialchars($row['full_name']) . '">' . htmlspecialchars($row['full_name']) . '</option>';
                }
                ?>
              </select>
            </div>

            <div class="eng-edit-field">
              <label for="edit_eng_scope">Scope</label>
              <textarea class="eng-edit-input" id="edit_eng_scope" name="scope" rows="3" placeholder="Enter scope"></textarea>
            </div>

            <div class="eng-edit-field eng-edit-checkbox-field">
              <label class="eng-edit-checkbox-label">
                <input type="checkbox" id="edit_eng_repeat_flag" name="repeat_flag" value="1">
                Repeat Engagement
              </label>
            </div>

            <div class="eng-edit-field">
              <label for="edit_eng_notes">Notes</label>
              <textarea class="eng-edit-input" id="edit_eng_notes" name="notes" rows="3"></textarea>
            </div>

            <div class="eng-edit-field">
              <label>Audit Types</label>
              <div class="eng-audit-type-list" id="edit_eng_audit_types">
                <?php
                require_once '../includes/db.php';
                $auditTypeQuery = $conn->query("SELECT audit_type_id, name, color FROM audit_types WHERE is_active = 1 ORDER BY name ASC");
                if ($auditTypeQuery && $auditTypeQuery->num_rows > 0):
                    while ($atRow = $auditTypeQuery->fetch_assoc()):
                ?>
                <label class="eng-audit-type-chip">
                  <input type="checkbox" name="audit_type_ids[]" value="<?php echo (int) $atRow['audit_type_id']; ?>" data-audit-type-name="<?php echo htmlspecialchars($atRow['name']); ?>">
                  <span class="eng-audit-type-dot" style="background:<?php echo htmlspecialchars($atRow['color']); ?>"></span>
                  <?php echo htmlspecialchars($atRow['name']); ?>
                </label>
                <?php
                    endwhile;
                else:
                ?>
                <div class="settings-empty-row" style="text-align:left; padding-left:0;">No audit types yet - add some under System Settings.</div>
                <?php endif; ?>
              </div>
            </div>

            <!-- TSC only matters for SOC 2 - hidden unless that's checked above. -->
            <div class="eng-edit-field d-none" id="edit_eng_tsc_wrap">
              <label>Trust Services Criteria (SOC 2)</label>
              <div class="eng-audit-type-list" id="edit_eng_tsc">
                <?php foreach (['Security', 'Availability', 'Confidentiality', 'Processing Integrity', 'Privacy'] as $tscOption): ?>
                <label class="eng-audit-type-chip">
                  <input type="checkbox" name="tsc[]" value="<?php echo htmlspecialchars($tscOption); ?>">
                  <?php echo htmlspecialchars($tscOption); ?>
                </label>
                <?php endforeach; ?>
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
