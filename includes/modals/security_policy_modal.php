<div class="modal fade" id="securityPolicyConfigModal" tabindex="-1" aria-labelledby="securityPolicyConfigLabel" aria-hidden="true">
      <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <form id="securityPolicyConfigForm" action="settings_backend.php" method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="securityPolicyConfigLabel">
                <i class="bi bi-shield-lock"></i> Security Policy Settings <br>
                <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important;">
                  Configure password policies, access control, and two-factor authentication
                </span>
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <!-- Password Policy Section -->
              <h6 class="mb-3">Password Policy</h6>

              <div class="mb-3">
                <label for="minPasswordLength" style="font-size: 14px;" class="form-label">Minimum Password Length</label>
                <input type="number" style="font-size: 14px;" class="form-control" id="minPasswordLength" name="min_password_length" min="1" max="128" value="<?php echo    htmlspecialchars($settings['min_password_length'] ?? 8); ?>" required>
              </div>

              <div class="row mb-3">
                <div class="col-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" style="font-size: 14px;" type="checkbox" id="requireNumbers" name="require_numbers" <?php if (!empty($settings  ['require_numbers']) && $settings['require_numbers'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" style="font-size: 14px;" for="requireNumbers">Require Numbers</label>
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" style="font-size: 14px;" type="checkbox" id="requireSymbols" name="require_symbols" <?php if (!empty($settings  ['require_symbols']) && $settings['require_symbols'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" style="font-size: 14px;" for="requireSymbols">Require Symbols</label>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" style="font-size: 14px;" type="checkbox" id="requireUppercase" name="require_uppercase" <?php if (!empty($settings  ['require_uppercase']) && $settings['require_uppercase'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" style="font-size: 14px;" for="requireUppercase">Require Uppercase</label>
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" style="font-size: 14px;" type="checkbox" id="requireLowercase" name="require_lowercase" <?php if (!empty($settings  ['require_lowercase']) && $settings['require_lowercase'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" style="font-size: 14px;" for="requireLowercase">Require Lowercase</label>
                  </div>
                </div>
              </div>

              <div class="mb-4">
                <label for="passwordExpiration" style="font-size: 14px;" class="form-label">Password Expiration (days)</label>
                <input type="number" style="font-size: 14px;" class="form-control" id="passwordExpiration" name="password_expiration_days" min="0" value="<?php echo    htmlspecialchars($settings['password_expiration_days'] ?? 0); ?>" required>
                <small  style="font-size: 12px;" class="text-muted">Set to 0 to disable password expiration</small>
              </div>

              <hr>

              <!-- Access Control Section -->
              <h6 class="mb-3">Access Control</h6>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="maxLoginAttempts" style="font-size: 14px;" class="form-label">Max Login Attempts</label>
                  <input type="number" style="font-size: 14px;" class="form-control" id="maxLoginAttempts" name="max_login_attempts" min="1" value="<?php echo  htmlspecialchars($settings['max_login_attempts'] ?? 5); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="lockoutDuration" style="font-size: 14px;" class="form-label">Lockout Duration (minutes)</label>
                  <input type="number" style="font-size: 14px;" class="form-control" id="lockoutDuration" name="lockout_duration_minutes" min="1" value="<?php echo     htmlspecialchars($settings['lockout_duration_minutes'] ?? 30); ?>" required>
                </div>
              </div>

              <div class="row mb-4">
                <div class="col-md-6">
                  <label for="sessionTimeout" style="font-size: 14px;" class="form-label">Session Timeout (minutes)</label>
                  <input type="number" style="font-size: 14px;" class="form-control" id="sessionTimeout" name="session_timeout_minutes" min="1" value="<?php echo   htmlspecialchars($settings['session_timeout_minutes'] ?? 60); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="apiRateLimit" class="form-label">API Rate Limit (per minute)</label>
                  <input type="number" class="form-control" id="apiRateLimit" name="api_rate_limit_per_minute" min="1" value="<?php echo htmlspecialchars($settings ['api_rate_limit_per_minute'] ?? 60); ?>" required>
                </div>
              </div>

              <hr>

              <!-- Two-Factor Authentication Section -->
              <h6 class="mb-3">Two-Factor Authentication (2FA)</h6>

              <div class="form-check form-switch mb-3">
                <input class="form-check-input" style="font-size: 14px;" type="checkbox" id="require2FAAllUsers" name="require_2fa_all_users" <?php if (!empty($settings    ['require_2fa_all_users']) && $settings['require_2fa_all_users'] === 'true') echo 'checked'; ?>>
                <label class="form-check-label" style="font-size: 14px;" for="require2FAAllUsers">
                  Require 2FA for all users
                  <br>
                  <small style="font-size: 12px;" class="text-muted">Force all users to enable two-factor authentication</small>
                </label>
              </div>

              <div class="form-check form-switch mb-3">
                <input class="form-check-input" style="font-size: 14px;" type="checkbox" id="require2FAAdmins" name="require_2fa_admins" <?php if (!empty($settings ['require_2fa_admins']) && $settings['require_2fa_admins'] === 'true') echo 'checked'; ?>>
                <label class="form-check-label" style="font-size: 14px;" for="require2FAAdmins">
                  Require 2FA for admins
                  <br>
                  <small style="font-size: 12px;" class="text-muted">Force admin users to enable two-factor authentication</small>
                </label>
              </div>
            </div>

            <div class="modal-footer">
              <a href="#" 
                 class="badge text-black p-2 text-decoration-none fw-medium" 
                 style="font-size: .875rem; border: 1px solid rgb(229,229,229);" 
                 data-bs-dismiss="modal">
                Cancel
              </a>

              <button type="submit" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18); border:none;">
                <i class="bi bi-save me-3"></i>Save Settings
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>