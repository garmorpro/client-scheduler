-- Adds a time-of-day alongside the existing weekly status call weekday, so
-- "Weekly Status Call: Tuesday" can become "Tuesday at 2:00 PM".

ALTER TABLE audit_engagement_timeline
  ADD COLUMN weekly_status_call_time TIME NULL AFTER weekly_status_call_day;
