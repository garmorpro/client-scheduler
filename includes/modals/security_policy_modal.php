<div class="modal fade" id="securityPolicyConfigModal" tabindex="-1" aria-labelledby="securityPolicyConfigLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 600px;">
    <div class="modal-content">
      <form id="securityPolicyConfigForm" action="settings_backend.php" method="POST">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="securityPolicyConfigLabel"><i class="bi bi-shield-lock me-2"></i>Security Policy Settings</div>
            <p class="text-muted" style="font-size: 12.5px; margin: 4px 0 0;">Configure password policies, access control, and two-factor authentication</p>
          </div>

          <div class="eng-edit-body">
            <!-- Password Policy -->
            <div class="detail-section-title">Password Policy</div>

            <div class="eng-edit-field">
              <label for="minPasswordLength">Minimum Password Length</label>
              <input type="number" class="eng-edit-input" id="minPasswordLength" name="min_password_length" min="1" max="128" value="<?php echo htmlspecialchars($settings['min_password_length'] ?? 8); ?>" required>
            </div>

            <div class="eng-edit-row" style="margin-bottom: 10px;">
              <div class="settings-toggle-row compact">
                <span class="settings-toggle-label">Require Numbers</span>
                <label class="rp-toggle">
                  <input type="checkbox" class="rp-toggle-input" id="requireNumbers" name="require_numbers" <?php if (!empty($settings['require_numbers']) && $settings['require_numbers'] === 'true') echo 'checked'; ?>>
                  <span class="rp-toggle-track"><span class="rp-toggle-thumb"></span></span>
                </label>
              </div>
              <div class="settings-toggle-row compact">
                <span class="settings-toggle-label">Require Symbols</span>
                <label class="rp-toggle">
                  <input type="checkbox" class="rp-toggle-input" id="requireSymbols" name="require_symbols" <?php if (!empty($settings['require_symbols']) && $settings['require_symbols'] === 'true') echo 'checked'; ?>>
                  <span class="rp-toggle-track"><span class="rp-toggle-thumb"></span></span>
                </label>
              </div>
            </div>

            <div class="eng-edit-row" style="margin-bottom: 14px;">
              <div class="settings-toggle-row compact">
                <span class="settings-toggle-label">Require Uppercase</span>
                <label class="rp-toggle">
                  <input type="checkbox" class="rp-toggle-input" id="requireUppercase" name="require_uppercase" <?php if (!empty($settings['require_uppercase']) && $settings['require_uppercase'] === 'true') echo 'checked'; ?>>
                  <span class="rp-toggle-track"><span class="rp-toggle-thumb"></span></span>
                </label>
              </div>
              <div class="settings-toggle-row compact">
                <span class="settings-toggle-label">Require Lowercase</span>
                <label class="rp-toggle">
                  <input type="checkbox" class="rp-toggle-input" id="requireLowercase" name="require_lowercase" <?php if (!empty($settings['require_lowercase']) && $settings['require_lowercase'] === 'true') echo 'checked'; ?>>
                  <span class="rp-toggle-track"><span class="rp-toggle-thumb"></span></span>
                </label>
              </div>
            </div>

            <div class="eng-edit-field">
              <label for="passwordExpiration">Password Expiration (days)</label>
              <input type="number" class="eng-edit-input" id="passwordExpiration" name="password_expiration_days" min="0" value="<?php echo htmlspecialchars($settings['password_expiration_days'] ?? 0); ?>" required>
              <div class="settings-hint">Set to 0 to disable password expiration</div>
            </div>

            <div class="settings-divider"></div>

            <!-- Access Control -->
            <div class="detail-section-title">Access Control</div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="maxLoginAttempts">Max Login Attempts</label>
                <input type="number" class="eng-edit-input" id="maxLoginAttempts" name="max_login_attempts" min="1" value="<?php echo htmlspecialchars($settings['max_login_attempts'] ?? 5); ?>" required>
              </div>
              <div class="eng-edit-field">
                <label for="lockoutDuration">Lockout Duration (minutes)</label>
                <input type="number" class="eng-edit-input" id="lockoutDuration" name="lockout_duration_minutes" min="1" value="<?php echo htmlspecialchars($settings['lockout_duration_minutes'] ?? 30); ?>" required>
              </div>
            </div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="sessionTimeout">Session Timeout (minutes)</label>
                <input type="number" class="eng-edit-input" id="sessionTimeout" name="session_timeout_minutes" min="1" value="<?php echo htmlspecialchars($settings['session_timeout_minutes'] ?? 60); ?>" required>
              </div>
              <div class="eng-edit-field">
                <label for="apiRateLimit">API Rate Limit (per minute)</label>
                <input type="number" class="eng-edit-input" id="apiRateLimit" name="api_rate_limit_per_minute" min="1" value="<?php echo htmlspecialchars($settings['api_rate_limit_per_minute'] ?? 60); ?>" required>
              </div>
            </div>

            <div class="settings-divider"></div>

            <!-- Two-Factor Authentication -->
            <div class="detail-section-title">Two-Factor Authentication (2FA)</div>

            <div class="settings-toggle-row">
              <div>
                <div class="settings-toggle-label">Require 2FA for all users</div>
                <div class="settings-toggle-sub">Force all users to enable two-factor authentication</div>
              </div>
              <label class="rp-toggle">
                <input type="checkbox" class="rp-toggle-input" id="require2FAAllUsers" name="require_2fa_all_users" <?php if (!empty($settings['require_2fa_all_users']) && $settings['require_2fa_all_users'] === 'true') echo 'checked'; ?>>
                <span class="rp-toggle-track"><span class="rp-toggle-thumb"></span></span>
              </label>
            </div>

            <div class="settings-toggle-row" style="margin-bottom: 0;">
              <div>
                <div class="settings-toggle-label">Require 2FA for admins</div>
                <div class="settings-toggle-sub">Force admin users to enable two-factor authentication</div>
              </div>
              <label class="rp-toggle">
                <input type="checkbox" class="rp-toggle-input" id="require2FAAdmins" name="require_2fa_admins" <?php if (!empty($settings['require_2fa_admins']) && $settings['require_2fa_admins'] === 'true') echo 'checked'; ?>>
                <span class="rp-toggle-track"><span class="rp-toggle-thumb"></span></span>
              </label>
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
