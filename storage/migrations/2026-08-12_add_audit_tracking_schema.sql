-- Phase 1 of folding Engagement Tracker's audit-lifecycle tracking into
-- Client Scheduler. Adds six new tables that hang off the existing
-- `engagements` row (no changes to `clients`, `engagements`,
-- `engagement_audit_types`, `audit_types`, `users`, or `entries`), plus five
-- new role_permissions columns for the DOL/timeline permission split.
--
-- See docs/client-scheduler-migration-plan.md in the Engagement Tracker repo
-- for the full plan and rationale. Team membership is deliberately NOT its
-- own table here — it's derived from `entries` (anyone with an hours row for
-- an engagement is considered staffed on it).
--
-- Run this once, the same way the other files in this directory are applied.
-- Every CREATE TABLE / ADD COLUMN is written defensively (IF NOT EXISTS),
-- so a partial or repeat run is safe — anything already created is skipped,
-- not re-errored.

-- ---------------------------------------------------------------------
-- audit_engagement_details — 1:1 with engagements. The audit-specific
-- fields Client Scheduler's own `engagements` row doesn't carry. Ported
-- from Engagement Tracker's `engagements` table (eng_location, eng_poc,
-- eng_tsc, eng_soc_type, eng_scope, eng_as_of_date, eng_start_period,
-- eng_end_period, eng_repeat, eng_notes) — audit type itself is NOT
-- duplicated here since engagement_audit_types already covers it.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_engagement_details (
  engagement_id INT NOT NULL,
  location VARCHAR(255) NULL,
  poc VARCHAR(255) NULL,
  tsc VARCHAR(255) NULL,
  soc_type VARCHAR(20) NULL,
  scope TEXT NULL,
  as_of_date DATE NULL,
  review_period_start DATE NULL,
  review_period_end DATE NULL,
  repeat_flag TINYINT(1) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  planning_doc_url VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (engagement_id),
  CONSTRAINT fk_aed_engagement FOREIGN KEY (engagement_id) REFERENCES engagements (engagement_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- audit_engagement_timeline — 1:1 with engagements. Every key date
-- Engagement Tracker tracks today, ported field-for-field from its
-- `engagement_timeline` table, each paired with its own `_completed_at`.
-- Fieldwork items carry a start/end range (they span a week, not a single
-- day); the weekly status call is a recurring day-of-week, optionally
-- linked across engagements via `weekly_status_call_group`.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_engagement_timeline (
  engagement_id INT NOT NULL,
  internal_planning_call_date DATE NULL,
  internal_planning_call_completed_at TIMESTAMP NULL,
  planning_memo_date DATE NULL,
  planning_memo_completed_at TIMESTAMP NULL,
  irl_due_date DATE NULL,
  irl_completed_at TIMESTAMP NULL,
  client_planning_call_date DATE NULL,
  client_planning_call_completed_at TIMESTAMP NULL,
  fieldwork_client_calls_start_date DATE NULL,
  fieldwork_client_calls_end_date DATE NULL,
  fieldwork_client_calls_completed_at TIMESTAMP NULL,
  fieldwork_documentation_start_date DATE NULL,
  fieldwork_documentation_end_date DATE NULL,
  fieldwork_documentation_completed_at TIMESTAMP NULL,
  leadsheet_date DATE NULL,
  leadsheet_completed_at TIMESTAMP NULL,
  conclusion_memo_date DATE NULL,
  conclusion_memo_completed_at TIMESTAMP NULL,
  draft_report_due_date DATE NULL,
  draft_report_completed_at TIMESTAMP NULL,
  final_report_date DATE NULL,
  final_report_completed_at TIMESTAMP NULL,
  archive_date DATE NULL,
  archive_completed_at TIMESTAMP NULL,
  weekly_status_call_day TINYINT NULL COMMENT '0=Sunday .. 6=Saturday, matches PHP date(w) and JS Date.getDay()',
  weekly_status_call_group VARCHAR(64) NULL COMMENT 'Stable join key for linked calls across engagements, defaults to this engagement_id',
  weekly_status_call_group_name VARCHAR(100) NULL COMMENT 'Human-editable display label for a linked call group',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (engagement_id),
  KEY weekly_status_call_group (weekly_status_call_group),
  CONSTRAINT fk_aet_engagement FOREIGN KEY (engagement_id) REFERENCES engagements (engagement_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- audit_engagement_milestones — 1:many. Custom milestones per engagement,
-- same shape as Engagement Tracker's `engagement_milestones` table.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_engagement_milestones (
  milestone_id INT NOT NULL AUTO_INCREMENT,
  engagement_id INT NOT NULL,
  milestone_type VARCHAR(100) NOT NULL,
  due_date DATE NULL,
  is_completed TINYINT(1) NOT NULL DEFAULT 0,
  completed_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (milestone_id),
  KEY engagement_id (engagement_id),
  CONSTRAINT fk_aem_engagement FOREIGN KEY (engagement_id) REFERENCES engagements (engagement_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- audit_dol_assignments — 1:many. One row per person + audit type +
-- criterion. Replaces Engagement Tracker's comma-separated
-- emp_soc2_dol-style columns with a real relational table — a genuine
-- upgrade, not just a port. No FK dependency on an `entries` row existing;
-- that's a workflow assumption (staffed via entries first), not an
-- enforced constraint.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_dol_assignments (
  id INT NOT NULL AUTO_INCREMENT,
  engagement_id INT NOT NULL,
  user_id INT NOT NULL,
  audit_type_id INT NOT NULL,
  criterion VARCHAR(50) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_assignment (engagement_id, audit_type_id, criterion),
  KEY user_id (user_id),
  KEY audit_type_id (audit_type_id),
  CONSTRAINT fk_ada_engagement FOREIGN KEY (engagement_id) REFERENCES engagements (engagement_id) ON DELETE CASCADE,
  CONSTRAINT fk_ada_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_ada_audit_type FOREIGN KEY (audit_type_id) REFERENCES audit_types (audit_type_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- audit_team_independence — one row per person per engagement. The
-- check/X/unanswered independence-from-client attestation, re-keyed to a
-- real user_id instead of a free-text name. NULL = unanswered.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_team_independence (
  engagement_id INT NOT NULL,
  user_id INT NOT NULL,
  independent ENUM('Y','N') NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (engagement_id, user_id),
  CONSTRAINT fk_ati_engagement FOREIGN KEY (engagement_id) REFERENCES engagements (engagement_id) ON DELETE CASCADE,
  CONSTRAINT fk_ati_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- dol_training_restrictions — many:many. Replaces the
-- employees.emp_restricted_criteria comma column with a proper junction
-- table, keyed to a real user_id.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS dol_training_restrictions (
  user_id INT NOT NULL,
  criterion VARCHAR(50) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, criterion),
  CONSTRAINT fk_dtr_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- role_permissions — add the DOL/timeline permission split. Three tiers,
-- not two: DOL editing, timeline DATE editing, and timeline item
-- COMPLETION are separate grants, since staff/interns can check items off
-- without being able to move a due date.
-- ---------------------------------------------------------------------
ALTER TABLE role_permissions
  ADD COLUMN IF NOT EXISTS manage_dol TINYINT(1) NOT NULL DEFAULT 0 AFTER access_system_settings,
  ADD COLUMN IF NOT EXISTS view_dol TINYINT(1) NOT NULL DEFAULT 0 AFTER manage_dol,
  ADD COLUMN IF NOT EXISTS manage_audit_timeline TINYINT(1) NOT NULL DEFAULT 0 AFTER view_dol,
  ADD COLUMN IF NOT EXISTS complete_audit_timeline_items TINYINT(1) NOT NULL DEFAULT 0 AFTER manage_audit_timeline,
  ADD COLUMN IF NOT EXISTS view_audit_timeline TINYINT(1) NOT NULL DEFAULT 0 AFTER complete_audit_timeline_items;

-- Default grants. admin bypasses role_permissions entirely (per
-- user_has_permission()) so it has no row here, same as every other
-- permission in this table.
UPDATE role_permissions SET manage_dol = 1, view_dol = 1, manage_audit_timeline = 1, complete_audit_timeline_items = 1, view_audit_timeline = 1 WHERE role = 'manager';
UPDATE role_permissions SET manage_dol = 1, view_dol = 1, manage_audit_timeline = 1, complete_audit_timeline_items = 1, view_audit_timeline = 1 WHERE role = 'senior';
UPDATE role_permissions SET manage_dol = 0, view_dol = 1, manage_audit_timeline = 0, complete_audit_timeline_items = 1, view_audit_timeline = 1 WHERE role = 'staff';
UPDATE role_permissions SET manage_dol = 0, view_dol = 1, manage_audit_timeline = 0, complete_audit_timeline_items = 1, view_audit_timeline = 1 WHERE role = 'intern';
UPDATE role_permissions SET manage_dol = 0, view_dol = 0, manage_audit_timeline = 0, complete_audit_timeline_items = 0, view_audit_timeline = 0 WHERE role = 'crm_team';
