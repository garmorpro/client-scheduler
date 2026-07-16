<div class="modal fade" id="emailNotifConfigModal" tabindex="-1" aria-labelledby="emailNotifConfigLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 600px;">
    <div class="modal-content">
      <form id="emailNotifConfigForm" action="settings_backend.php" method="POST">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="emailNotifConfigLabel"><i class="bi bi-envelope me-2"></i>Email Notification Settings</div>
            <p class="text-muted" style="font-size: 12.5px; margin: 4px 0 0;">Configure email notifications and SMTP settings</p>
          </div>

          <div class="eng-edit-body">
            <!-- General Settings -->
            <div class="detail-section-title">General Settings</div>

            <div class="settings-toggle-row">
              <div>
                <div class="settings-toggle-label">Enable Email Notifications</div>
                <div class="settings-toggle-sub">Master switch for all email notifications</div>
              </div>
              <label class="rp-toggle">
                <input type="checkbox" class="rp-toggle-input" id="enableEmailNotifications" name="enable_email_notifications" <?php if (!empty($settings['enable_email_notifications']) && $settings['enable_email_notifications'] === 'true') echo 'checked'; ?>>
                <span class="rp-toggle-track"><span class="rp-toggle-thumb"></span></span>
              </label>
            </div>

            <!-- Notification Frequency -->
            <div class="detail-section-title">Notification Frequency</div>
            <div class="eng-edit-field">
              <label for="notificationFrequency">Frequency</label>
              <select class="eng-edit-input" id="notificationFrequency" name="notification_frequency" required>
                <option value="immediately" <?php if (($settings['notification_frequency'] ?? '') === 'immediately') echo 'selected'; ?>>Immediately</option>
                <option value="hourly_digest" <?php if (($settings['notification_frequency'] ?? '') === 'hourly_digest') echo 'selected'; ?>>Hourly Digest</option>
                <option value="daily_digest" <?php if (($settings['notification_frequency'] ?? '') === 'daily_digest') echo 'selected'; ?>>Daily Digest</option>
                <option value="weekly_digest" <?php if (($settings['notification_frequency'] ?? '') === 'weekly_digest') echo 'selected'; ?>>Weekly Digest</option>
              </select>
            </div>

            <div class="settings-divider"></div>

            <!-- SMTP Configuration -->
            <div class="detail-section-title">SMTP Configuration</div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="smtpServer">SMTP Server</label>
                <input type="text" class="eng-edit-input" id="smtpServer" name="smtp_server" placeholder="smtp.example.com" value="<?php echo htmlspecialchars($settings['smtp_server'] ?? '', ENT_QUOTES); ?>" required>
              </div>
              <div class="eng-edit-field">
                <label for="smtpPort">SMTP Port</label>
                <input type="number" class="eng-edit-input" id="smtpPort" name="smtp_port" placeholder="587" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '', ENT_QUOTES); ?>" required>
              </div>
            </div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="smtpUsername">Username</label>
                <input type="text" class="eng-edit-input" id="smtpUsername" name="smtp_username" placeholder="user@example.com" value="<?php echo htmlspecialchars($settings['smtp_username'] ?? '', ENT_QUOTES); ?>" required>
              </div>
              <div class="eng-edit-field">
                <label for="smtpPassword">Password</label>
                <input type="password" class="eng-edit-input" id="smtpPassword" name="smtp_password" placeholder="<?php echo !empty($settings['smtp_password']) ? 'Leave blank to keep current password' : ''; ?>" autocomplete="new-password">
              </div>
            </div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="senderName">Sender Name</label>
                <input type="text" class="eng-edit-input" id="senderName" name="sender_name" placeholder="Your Company" value="<?php echo htmlspecialchars($settings['sender_name'] ?? '', ENT_QUOTES); ?>" required>
              </div>
              <div class="eng-edit-field">
                <label for="senderEmail">Sender Email</label>
                <input type="email" class="eng-edit-input" id="senderEmail" name="sender_email" placeholder="no-reply@example.com" value="<?php echo htmlspecialchars($settings['sender_email'] ?? '', ENT_QUOTES); ?>" required>
              </div>
            </div>

            <div class="settings-divider"></div>

            <!-- Test Configuration -->
            <div class="detail-section-title">Test Configuration</div>
            <div class="eng-edit-field">
              <label for="testEmail">Send a test email to</label>
              <input type="email" class="eng-edit-input" id="testEmail" placeholder="test@example.com">
            </div>
            <button type="button" id="sendTestEmailBtn" class="settings-action-btn" disabled>
              <i class="bi bi-envelope"></i> Send Test Email
            </button>
            <div id="testEmailStatus" class="settings-status-msg d-none"></div>
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
