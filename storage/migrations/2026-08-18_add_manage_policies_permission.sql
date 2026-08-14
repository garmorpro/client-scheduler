-- Policies get their own dedicated "Edit" permission in the Role
-- Permissions matrix, instead of piggybacking on the broader
-- access_system_settings toggle - per Garrett, viewing policies is
-- unconditional for every logged-in user regardless of role (so there's
-- no matching "view_policies" toggle, just this one), but editing/deleting
-- them should be its own decision, separate from the rest of System
-- Settings.
--
-- Backfilled from access_system_settings so nobody who could already
-- manage policies loses that ability at migration time - the two toggles
-- are independent from here on, an admin can now flip either one alone.

ALTER TABLE role_permissions
  ADD COLUMN manage_policies TINYINT(1) NOT NULL DEFAULT 0 AFTER access_system_settings;

UPDATE role_permissions SET manage_policies = access_system_settings;
