-- Adds a view-only "My Schedule" permission. Unlike the other permission
-- groups, this one has no manage tier - a person can only ever see their own
-- schedule, never someone else's, so there's nothing to "manage" here.
--
-- Defaults every existing role to seeing My Schedule (matching current
-- behavior - everyone but Admin/Service Account already lands there), then
-- turns it off specifically for CRM Team, who have no engagement hours of
-- their own to view.

ALTER TABLE role_permissions
  ADD COLUMN view_my_schedule tinyint(1) NOT NULL DEFAULT 1 AFTER manage_master_schedule;

UPDATE role_permissions SET view_my_schedule = 0 WHERE role = 'crm_team';
