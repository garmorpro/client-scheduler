-- Dedup log for audit due-date email digests (includes/audit_notifications.php).
-- Ported concept from Engagement Tracker's engagement_notifications table,
-- but much simpler - no in-app "is_read" state, since this isn't feeding an
-- in-app bell, just preventing the same item from being emailed twice.
-- One row per (engagement, field) that's already been notified on; a
-- milestone's field is 'milestone_<id>' to keep it distinct from timeline
-- date column names.
CREATE TABLE IF NOT EXISTS audit_notification_log (
  id INT NOT NULL AUTO_INCREMENT,
  engagement_id INT NOT NULL,
  notif_field VARCHAR(100) NOT NULL,
  notified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_notif (engagement_id, notif_field),
  KEY engagement_id (engagement_id),
  CONSTRAINT fk_anl_engagement FOREIGN KEY (engagement_id) REFERENCES engagements (engagement_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
