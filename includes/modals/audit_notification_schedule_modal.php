<div class="modal fade" id="auditNotificationScheduleModal" tabindex="-1" aria-labelledby="auditNotificationScheduleLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
    <div class="modal-content">
      <form id="auditNotificationScheduleForm" novalidate>
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="auditNotificationScheduleLabel"><i class="bi bi-clock-history me-2"></i>Audit Notification Schedule</div>
            <p class="text-muted" style="font-size: 12.5px; margin: 4px 0 0;">When to send the daily upcoming-due-date email digest</p>
          </div>

          <div class="eng-edit-body">
            <div class="settings-toggle-row">
              <div>
                <div class="settings-toggle-label">Send Daily Digest</div>
                <div class="settings-toggle-sub">Master switch — off removes the scheduled job entirely, not just skips sending</div>
              </div>
              <label class="rp-toggle">
                <input type="checkbox" class="rp-toggle-input" id="auditNotifEnabled">
                <span class="rp-toggle-track"><span class="rp-toggle-thumb"></span></span>
              </label>
            </div>

            <div class="eng-edit-field">
              <label for="auditNotifTime">Send Time</label>
              <input type="time" class="eng-edit-input" id="auditNotifTime">
            </div>

            <div class="eng-edit-field">
              <label>Days</label>
              <div class="audit-notif-days" id="auditNotifDays">
                <button type="button" class="audit-notif-day-btn" data-day="0" title="Sunday">S</button>
                <button type="button" class="audit-notif-day-btn" data-day="1" title="Monday">M</button>
                <button type="button" class="audit-notif-day-btn" data-day="2" title="Tuesday">T</button>
                <button type="button" class="audit-notif-day-btn" data-day="3" title="Wednesday">W</button>
                <button type="button" class="audit-notif-day-btn" data-day="4" title="Thursday">T</button>
                <button type="button" class="audit-notif-day-btn" data-day="5" title="Friday">F</button>
                <button type="button" class="audit-notif-day-btn" data-day="6" title="Saturday">S</button>
              </div>
            </div>

            <div class="settings-status-banner" id="auditNotifCrontabStatus" style="display:none;"></div>
          </div>
        </div>

        <div class="eng-edit-footer">
          <span class="rp-dirty-hint" id="auditNotifSaveHint"></span>
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="eng-edit-btn-save" id="auditNotifSaveBtn">Save Schedule</button>
        </div>
      </form>
    </div>
  </div>
</div>
