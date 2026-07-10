<div class="modal fade" id="backupConfigModal" tabindex="-1" aria-labelledby="backupConfigLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 600px;">
    <div class="modal-content">
      <form id="backupConfigForm" novalidate>
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="backupConfigLabel"><i class="bi bi-hdd-stack me-2"></i>Backup Configuration Settings</div>
            <p class="text-muted" style="font-size: 12.5px; margin: 4px 0 0;">Configure automated backup schedule and local storage</p>
          </div>

          <div class="eng-edit-body">
            <!-- Last Backup Info -->
            <div class="settings-status-banner">
              <i class="bi bi-check2-circle"></i>
              <span>Last backup: <strong><?php echo htmlspecialchars($settings['last_backup_datetime'] ?? 'Never'); ?></strong>
                &nbsp;(<?php echo htmlspecialchars($settings['last_backup_size'] ?? '0 GB'); ?>)</span>
            </div>

            <!-- Backup Schedule -->
            <div class="detail-section-title">Backup Schedule</div>

            <div class="settings-toggle-row">
              <div>
                <div class="settings-toggle-label">Enable Automated Backups</div>
                <div class="settings-toggle-sub">Master switch for automated backups</div>
              </div>
              <label class="rp-toggle">
                <input type="checkbox" class="rp-toggle-input" id="enableAutomatedBackups" name="enable_automated_backups" value="true" <?php if (!empty($settings['enable_automated_backups']) && $settings['enable_automated_backups'] === 'true') echo 'checked'; ?>>
                <span class="rp-toggle-track"><span class="rp-toggle-thumb"></span></span>
              </label>
            </div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="backupFrequency">Backup Frequency</label>
                <select class="eng-edit-input" id="backupFrequency" name="backup_frequency" required>
                  <option value="hourly" <?php if (($settings['backup_frequency'] ?? '') === 'hourly') echo 'selected'; ?>>Every Hour</option>
                  <option value="daily" <?php if (($settings['backup_frequency'] ?? '') === 'daily') echo 'selected'; ?>>Daily</option>
                  <option value="weekly" <?php if (($settings['backup_frequency'] ?? '') === 'weekly') echo 'selected'; ?>>Weekly</option>
                  <option value="monthly" <?php if (($settings['backup_frequency'] ?? '') === 'monthly') echo 'selected'; ?>>Monthly</option>
                </select>
              </div>
              <div class="eng-edit-field">
                <label for="backupTime">Backup Time</label>
                <input type="time" class="eng-edit-input" id="backupTime" name="backup_time" value="<?php echo htmlspecialchars($settings['backup_time'] ?? '', ENT_QUOTES); ?>" required>
              </div>
            </div>

            <div class="eng-edit-field">
              <label for="retentionPeriod">Retention Period (days)</label>
              <input type="number" min="1" class="eng-edit-input" id="retentionPeriod" name="retention_period_days" value="<?php echo htmlspecialchars($settings['retention_period_days'] ?? '', ENT_QUOTES); ?>" required>
            </div>

            <div class="settings-divider"></div>

            <!-- Local Storage Directory -->
            <div class="detail-section-title">Local Backup Directory</div>
            <div class="eng-edit-field">
              <label for="localBackupDir">Directory Path</label>
              <input type="text" class="eng-edit-input" id="localBackupDir" name="local_backup_directory" placeholder="/path/to/backup/folder" value="<?php echo htmlspecialchars($settings['local_backup_directory'] ?? '', ENT_QUOTES); ?>" required>
              <div class="settings-hint">Enter full path on the server where backups should be saved.</div>
            </div>

            <div class="settings-divider"></div>

            <!-- Test Configuration -->
            <div class="detail-section-title">Test Configuration</div>
            <button type="button" id="runTestBackupBtn" class="settings-action-btn"><i class="bi bi-play-fill"></i> Run Test Backup</button>

            <div class="settings-divider"></div>

            <!-- Recent Backups -->
            <div class="detail-section-title">Recent Backups</div>
            <div id="backupHistoryList" class="eng-vm-emp-list">
              <div class="settings-empty-row">Loading...</div>
            </div>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="eng-edit-btn-save"><i class="bi bi-save me-2"></i>Save Settings</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
