<div class="modal fade" id="addEngagementModal" tabindex="-1" aria-labelledby="addEngagementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form id="addEngagementForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addEngagementModalLabel">Add Engagement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="client_id" id="modal_client_id">
          <input type="hidden" name="client_name" id="modal_client_name">
          <input type="hidden" name="year" id="modal_year" value="<?php echo date('Y'); ?>">

          <div class="mb-3">
            <label for="budget_hours" class="form-label">Budget Hours</label>
            <input type="number" min="0" class="form-control" id="budget_hours" name="budget_hours" required>
          </div>

          <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
              <option value="confirmed">Confirmed</option>
              <option value="pending">Pending</option>
              <option value="not_confirmed">Not Confirmed</option>
            </select>
          </div>

          <!-- Manager dropdown -->
          <div class="mb-3">
    <label for="manager" class="form-label">Manager</label>
    <select class="form-select" id="manager" name="manager" required>
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

          <div class="mb-3">
            <label class="form-label">Audit Types</label>
            <div class="eng-audit-type-list" id="add_eng_audit_types">
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
          <div class="mb-3 d-none" id="add_eng_tsc_wrap">
            <label class="form-label">Trust Services Criteria (SOC 2)</label>
            <div class="eng-audit-type-list">
              <?php foreach (['Security', 'Availability', 'Confidentiality', 'Processing Integrity', 'Privacy'] as $tscOption): ?>
              <label class="eng-audit-type-chip">
                <input type="checkbox" name="tsc[]" value="<?php echo htmlspecialchars($tscOption); ?>" <?php echo $tscOption === 'Security' ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($tscOption); ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="modal-footer p-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Add</button>
        </div>
      </form>
    </div>
  </div>
</div>
