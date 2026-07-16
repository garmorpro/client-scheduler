-- Adds a dedicated View/Manage permission pair for Master Schedule,
-- decoupled from Clients & Engagements. Previously, seeing the schedule at
-- all required manage_clients_engagements, so seniors/staff/interns had no
-- way to view it read-only without also being able to edit clients and
-- engagements.
--
-- Migrates existing behavior forward (manager/crm_team keep whatever
-- edit access to the schedule they already had via manage_clients_engagements)
-- and grants senior/staff/intern read-only access to the schedule, per request.

ALTER TABLE role_permissions
  ADD COLUMN view_master_schedule tinyint(1) NOT NULL DEFAULT 0 AFTER view_clients_engagements,
  ADD COLUMN manage_master_schedule tinyint(1) NOT NULL DEFAULT 0 AFTER view_master_schedule;

UPDATE role_permissions
SET manage_master_schedule = manage_clients_engagements,
    view_master_schedule = CASE
        WHEN role IN ('senior', 'staff', 'intern') THEN 1
        ELSE manage_clients_engagements
    END;
