<div class="modal fade" id="emailNotifConfigModal" tabindex="-1" aria-labelledby="emailNotifConfigLabel" aria-hidden="true">
      <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <form id="emailNotifConfigForm" action="settings_backend.php" method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="emailNotifConfigLabel">
                <i class="bi bi-envelope"></i> Email Notification Settings <br>
                <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important;">Configure email notifications and SMTP settings</span>
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <!-- General Settings -->
              <h6 class="mb-2">General Settings</h6>
              <div class="form-check form-switch mb-4" style="padding-left: 0; margin-left: 0;">
                <label class="form-check-label float-start m-0" for="enableEmailNotifications">
                  Enable Email Notifications (Master switch) <br>
                  <span class="text-muted" style="font-size: 12px;">
                    Master switch for all email notifications
                  </span>
                </label>
                <input class="form-check-input float-end" type="checkbox" id="enableEmailNotifications" name="enable_email_notifications" <?php if (!empty($settings    ['enable_email_notifications']) && $settings['enable_email_notifications'] === 'true') echo 'checked'; ?>>
              </div>

              <!-- Notification Types -->
              <!-- <h6 class="mb-2">Notification Types</h6>
              <div class="row mb-2">
                <div class="col-6">
                  <div class="form-check form-switch mb-2">
                    <input type="hidden" name="notification_types[entry_updates]" value="false">
                    <input class="form-check-input" type="checkbox" id="entryUpdates" name="notification_types[entry_updates]" value="true" <?php if (!empty($settings['notification_types[entry_updates]']) && $settings['notification_types[entry_updates]'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" for="entryUpdates">Entry Notifications</label>
                  </div>
                                
                  <div class="form-check form-switch mb-2">
                    <input type="hidden" name="notification_types[timeoff_notifications]" value="false">
                    <input class="form-check-input" type="checkbox" id="timeoffNotifications" name="notification_types[timeoff_notifications]" value="true" <?php if (!empty($settings['notification_types[timeoff_notifications]']) && $settings['notification_types[timeoff_notifications]'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" for="timeoffNotifications">Timeoff Notifications</label>
                  </div>
                </div>
                                
                <div class="col-6">
                  <div class="form-check form-switch mb-2">
                    <input type="hidden" name="notification_types[user_notifications]" value="false">
                    <input class="form-check-input" type="checkbox" id="userNotifications" name="notification_types[user_notifications]" value="true" <?php if (!empty($settings['notification_types[user_notifications]']) && $settings['notification_types[user_notifications]'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" for="userNotifications">User Notifications</label>
                  </div>
                                
                  <div class="form-check form-switch mb-2">
                    <input type="hidden" name="notification_types[login_alerts]" value="false">
                    <input class="form-check-input" type="checkbox" id="loginAlerts" name="notification_types[login_alerts]" value="true" <?php if (!empty($settings['notification_types[login_alerts]']) && $settings['notification_types[login_alerts]'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" for="loginAlerts">Login Alerts</label>
                  </div>
                </div>
              </div> -->


              <!-- Notification Frequency -->
              <h6 class="mb-2">Notification Frequency</h6>
              <select class="form-select mb-4" id="notificationFrequency" name="notification_frequency" required>
                <option value="immediately" <?php if (($settings['notification_frequency'] ?? '') === 'immediately') echo 'selected'; ?>>Immediately</option>
                <option value="hourly_digest" <?php if (($settings['notification_frequency'] ?? '') === 'hourly_digest') echo 'selected'; ?>>Hourly Digest</option>
                <option value="daily_digest" <?php if (($settings['notification_frequency'] ?? '') === 'daily_digest') echo 'selected'; ?>>Daily Digest</option>
                <option value="weekly_digest" <?php if (($settings['notification_frequency'] ?? '') === 'weekly_digest') echo 'selected'; ?>>Weekly Digest</option>
              </select>

              <!-- SMTP Configuration -->
              <h6 class="mb-3">SMTP Configuration</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label for="smtpServer" class="form-label">SMTP Server</label>
                  <input type="text" class="form-control" id="smtpServer" name="smtp_server" placeholder="smtp.example.com" value="<?php echo htmlspecialchars($settings    ['smtp_server'] ?? '', ENT_QUOTES); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="smtpPort" class="form-label">SMTP Port</label>
                  <input type="number" class="form-control" id="smtpPort" name="smtp_port" placeholder="587" value="<?php echo htmlspecialchars($settings   ['smtp_port'] ?? '', ENT_QUOTES); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="smtpUsername" class="form-label">Username</label>
                  <input type="text" class="form-control" id="smtpUsername" name="smtp_username" placeholder="user@example.com" value="<?php echo htmlspecialchars  ($settings['smtp_username'] ?? '', ENT_QUOTES); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="smtpPassword" class="form-label">Password</label>
                  <input type="password" class="form-control" id="smtpPassword" name="smtp_password" placeholder="••••••••" value="<?php echo htmlspecialchars($settings    ['smtp_password'] ?? '', ENT_QUOTES); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="senderName" class="form-label">Sender Name</label>
                  <input type="text" class="form-control" id="senderName" name="sender_name" placeholder="Your Company" value="<?php echo htmlspecialchars($settings    ['sender_name'] ?? '', ENT_QUOTES); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="senderEmail" class="form-label">Sender Email</label>
                  <input type="email" class="form-control" id="senderEmail" name="sender_email" placeholder="no-reply@example.com" value="<?php echo htmlspecialchars   ($settings['sender_email'] ?? '', ENT_QUOTES); ?>" required>
                </div>
              </div>

              <!-- Test Configuration -->
              <h6 class="mb-3">Test Configuration</h6>
              <div class="mb-3">
                <input type="email" class="form-control mb-3" id="testEmail" placeholder="test@example.com" aria-label="Test email">

                <a href="#"
                   id="sendTestEmailBtn"
                   class="badge text-black p-2 text-decoration-none fw-medium disabled"
                   style="font-size: .875rem; border: 1px solid rgb(229,229,229); pointer-events: none; opacity: 0.5;">
                  <i class="bi bi-envelope me-3"></i>Send Test Email
                </a>
              </div>

              <div id="testEmailStatus" class="small text-success d-none mb-3"></div>
            </div>

            <div class="modal-footer">
              <a href="#" 
                 class="badge text-black p-2 text-decoration-none fw-medium" 
                 style="font-size: .875rem; border: 1px solid rgb(229,229,229);" 
                 data-bs-dismiss="modal">
                Cancel
              </a>

              <button type="submit" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18); border:none;">
                <i class="bi bi-person-plus me-3"></i>Save Settings
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>