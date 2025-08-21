<div class="modal fade" id="backupConfigModal" tabindex="-1" aria-labelledby="backupConfigLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form id="backupConfigForm" action="settings_backend.php" method="POST" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="backupConfigLabel">
            <i class="bi bi-hdd-stack"></i> Backup Configuration Settings <br>
            <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important;">
              Configure automated backup schedule and local storage
            </span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <!-- Last Backup Info Card -->
          <div class="d-flex align-items-center p-2 mb-4 rounded-3" style="background-color: #f7f7f7; border: 1px solid #ddd;">
            <div class="me-3" style="font-size: 14px !important; color: #6c757d;">
              <i class="bi bi-check2-circle"></i>
            </div>
            <div style="color: #6c757d; font-size: 14px !important;">
              Last backup: <?php echo htmlspecialchars($settings['last_backup_datetime'] ?? 'Never'); ?> 
              &nbsp; (<?php echo htmlspecialchars($settings['last_backup_size'] ?? '0 GB'); ?>)
            </div>
          </div>

          <!-- Backup Schedule -->
          <h6 class="mb-3">Backup Schedule</h6>
          <div class="form-check form-switch mb-4" style="padding-left: 0; margin-left: 0;">
            <input class="form-check-input float-end" style="font-size: 14px !important;" type="checkbox" id="enableAutomatedBackups" name="enable_automated_backups" value="true" <?php if (!empty($settings['enable_automated_backups']) && $settings['enable_automated_backups'] === 'true') echo 'checked'; ?>>
            <label class="form-check-label float-start" style="font-size: 14px !important;" for="enableAutomatedBackups">
              Enable Automated Backups <br>
              <span class="text-muted" style="font-size: 12px;">Master switch for automated backups</span>
            </label>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="backupFrequency" class="form-label" style="font-size: 14px !important;">Backup Frequency</label>
              <select class="form-select" id="backupFrequency" style="font-size: 14px !important;" name="backup_frequency" required>
                <option value="hourly" <?php if (($settings['backup_frequency'] ?? '') === 'hourly') echo 'selected'; ?>>Every Hour</option>
                <option value="daily" <?php if (($settings['backup_frequency'] ?? '') === 'daily') echo 'selected'; ?>>Daily</option>
                <option value="weekly" <?php if (($settings['backup_frequency'] ?? '') === 'weekly') echo 'selected'; ?>>Weekly</option>
                <option value="monthly" <?php if (($settings['backup_frequency'] ?? '') === 'monthly') echo 'selected'; ?>>Monthly</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="backupTime" class="form-label" style="font-size: 14px !important;">Backup Time</label>
              <input type="time" class="form-control" id="backupTime" style="font-size: 14px !important;" name="backup_time" value="<?php echo htmlspecialchars($settings['backup_time'] ?? '', ENT_QUOTES); ?>" required>
            </div>
          </div>

          <div class="mb-4">
            <label for="retentionPeriod" style="font-size: 14px !important;" class="form-label">Retention Period (days)</label>
            <input type="number" min="1" class="form-control" style="font-size: 14px !important;" id="retentionPeriod" name="retention_period_days" value="<?php echo htmlspecialchars($settings['retention_period_days'] ?? '', ENT_QUOTES); ?>" required>
          </div>

          <hr>

          <!-- Local Storage Directory -->
          <h6 class="mb-3">Local Backup Directory</h6>
          <div class="mb-3">
            <label for="localBackupDir" class="form-label" style="font-size: 14px !important;">Directory Path</label>
            <input type="text" class="form-control" id="localBackupDir" name="local_backup_directory" placeholder="/path/to/backup/folder" value="<?php echo htmlspecialchars($settings['local_backup_directory'] ?? '', ENT_QUOTES); ?>" required>
            <div class="form-text" style="font-size: 12px;">Enter full path on the server where backups should be saved.</div>
          </div>

          <hr>

          <!-- Test Configuration -->
          <h6 class="mb-3">Test Configuration</h6>
          <button type="button" style="font-size: 14px !important;" id="runTestBackupBtn" class="btn btn-primary mb-3">Run Test Backup</button>

        </div>

        <div class="modal-footer">
          <a href="#" class="badge text-black p-2 text-decoration-none fw-medium" style="font-size: .875rem; border: 1px solid rgb(229,229,229);" data-bs-dismiss="modal">Cancel</a>
          <button type="submit" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18); border:none;">
            <i class="bi bi-save me-2"></i>Save Settings
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
