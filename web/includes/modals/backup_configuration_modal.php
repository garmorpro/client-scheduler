<div class="modal fade" id="backupConfigModal" tabindex="-1" aria-labelledby="backupConfigLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form id="backupConfigForm" action="settings_backend.php" method="POST" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="backupConfigLabel">
            <i class="bi bi-hdd-stack"></i> Backup Configuration Settings <br>
            <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important;">
              Configure automated backup schedule and storage options
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

          <!-- Storage Location -->
          <h6 class="mb-3">Storage Location</h6>
          <select class="form-select mb-3" style="font-size: 14px !important;" id="storageLocation" name="storage_location" required>
            <?php 
            $storageOptions = ['local' => 'Local Storage', 'cloud' => 'Cloud Storage', 'network' => 'Network Storage'];
            foreach ($storageOptions as $val => $label) {
              $selected = (($settings['storage_location'] ?? '') === $val) ? 'selected' : '';
              echo "<option value=\"$val\" $selected>$label</option>";
            }
            ?>
          </select>

          <!-- Local Storage Directory -->
          <div id="localStorageSettings" style="display: none;" class="mb-3">
            <label for="localBackupDir" class="form-label" style="font-size: 14px !important;">Local Backup Directory</label>
            <input type="text" class="form-control" id="localBackupDir" name="local_backup_directory" placeholder="/path/to/backup/folder" value="<?php echo htmlspecialchars($settings['local_backup_directory'] ?? '', ENT_QUOTES); ?>">
            <div class="form-text" style="font-size: 12px;">Enter full path on the server where backups should be saved.</div>
          </div>

          <!-- Cloud Storage Settings -->
          <div id="cloudStorageSettings" style="display: none;">
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="cloudProvider" style="font-size: 14px !important;" class="form-label">Cloud Provider</label>
                <select class="form-select" style="font-size: 14px !important;" id="cloudProvider" name="cloud_provider">
                  <?php
                  $cloudProviders = ['aws' => 'Amazon S3', 'azure' => 'Azure Blob', 'gcp' => 'Google Cloud', 'dropbox' => 'Dropbox'];
                  foreach ($cloudProviders as $val => $label) {
                    $selected = (($settings['cloud_provider'] ?? '') === $val) ? 'selected' : '';
                    echo "<option value=\"$val\" $selected>$label</option>";
                  }
                  ?>
                </select>
              </div>
              <div class="col-md-6">
                <label for="bucketName" style="font-size: 14px !important;" class="form-label">Bucket/Container Name</label>
                <input type="text" style="font-size: 14px !important;" class="form-control" id="bucketName" name="bucket_name" value="<?php echo htmlspecialchars($settings['bucket_name'] ?? '', ENT_QUOTES); ?>" placeholder="Enter bucket or container name">
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-6">
                <label for="accessKey" style="font-size: 14px !important;" class="form-label">Access Key</label>
                <input type="text" style="font-size: 14px !important;" class="form-control" id="accessKey" name="access_key" value="<?php echo htmlspecialchars($settings['access_key'] ?? '', ENT_QUOTES); ?>" placeholder="Enter access key">
              </div>
              <div class="col-md-6">
                <label for="secretKey" style="font-size: 14px !important;" class="form-label">Secret Key</label>
                <input type="password" style="font-size: 14px !important;" class="form-control" id="secretKey" name="secret_key" value="<?php echo htmlspecialchars($settings['secret_key'] ?? '', ENT_QUOTES); ?>" placeholder="Enter secret key">
              </div>
            </div>

            <div class="mb-3">
              <label for="region" style="font-size: 14px !important;" class="form-label">Region</label>
              <input type="text" style="font-size: 14px !important;" class="form-control" id="region" name="region" value="<?php echo htmlspecialchars($settings['region'] ?? '', ENT_QUOTES); ?>" placeholder="Enter region">
            </div>
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