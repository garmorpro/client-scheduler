-- Playground / demo seed data for client_scheduler.
--
-- Wipes every application table and replaces it with fictional data so the
-- app can be demoed without showing any real client/employee information.
-- Safe to re-run any time you want to reset the playground back to a known
-- state before a presentation.
--
-- All seeded logins share the password: Playground2026!

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE backup_history;
TRUNCATE TABLE client_engagement_history;
TRUNCATE TABLE clients;
TRUNCATE TABLE engagements;
TRUNCATE TABLE entries;
TRUNCATE TABLE ms_users;
TRUNCATE TABLE policies;
TRUNCATE TABLE role_permissions;
TRUNCATE TABLE service_accounts;
TRUNCATE TABLE settings;
TRUNCATE TABLE system_activity_log;
TRUNCATE TABLE time_off;
TRUNCATE TABLE time_off_comments;
TRUNCATE TABLE users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Users (password for all: Playground2026!)
-- ---------------------------------------------------------------------
INSERT INTO users (user_id, email, password, full_name, job_title, role, manager_id, status, theme_mode, last_active, created_at) VALUES
(1, 'admin@playground.demo',        '$2y$12$ZAz6GGzsbOvUdrBOpTqVL.axPCsBvgcqYmqC8j8jdWQwEoMCM937a', 'Alex Morgan',    'Managing Director',     'admin',     NULL, 'active', 'light', '2026-07-15 17:40:00', '2025-01-06 09:00:00'),
(2, 'jordan.reyes@playground.demo', '$2y$12$ZAz6GGzsbOvUdrBOpTqVL.axPCsBvgcqYmqC8j8jdWQwEoMCM937a', 'Jordan Reyes',   'Engagement Manager',    'manager',   NULL, 'active', 'light', '2026-07-15 16:05:00', '2025-01-13 09:00:00'),
(3, 'casey.nguyen@playground.demo', '$2y$12$ZAz6GGzsbOvUdrBOpTqVL.axPCsBvgcqYmqC8j8jdWQwEoMCM937a', 'Casey Nguyen',   'Engagement Manager',    'manager',   NULL, 'active', 'light', '2026-07-14 11:22:00', '2025-02-03 09:00:00'),
(4, 'priya.shah@playground.demo',   '$2y$12$ZAz6GGzsbOvUdrBOpTqVL.axPCsBvgcqYmqC8j8jdWQwEoMCM937a', 'Priya Shah',     'Senior Associate',      'senior',    2,    'active', 'light', '2026-07-15 09:12:00', '2025-03-10 09:00:00'),
(5, 'marcus.webb@playground.demo',  '$2y$12$ZAz6GGzsbOvUdrBOpTqVL.axPCsBvgcqYmqC8j8jdWQwEoMCM937a', 'Marcus Webb',    'Senior Associate',      'senior',    3,    'active', 'light', '2026-07-13 14:47:00', '2025-03-17 09:00:00'),
(6, 'taylor.kim@playground.demo',   '$2y$12$ZAz6GGzsbOvUdrBOpTqVL.axPCsBvgcqYmqC8j8jdWQwEoMCM937a', 'Taylor Kim',     'Associate',             'staff',     2,    'active', 'light', '2026-07-15 18:03:00', '2025-04-21 09:00:00'),
(7, 'sam.rivera@playground.demo',   '$2y$12$ZAz6GGzsbOvUdrBOpTqVL.axPCsBvgcqYmqC8j8jdWQwEoMCM937a', 'Sam Rivera',     'Associate',             'staff',     3,    'active', 'light', '2026-07-15 10:30:00', '2025-05-05 09:00:00'),
(8, 'devon.clarke@playground.demo', '$2y$12$ZAz6GGzsbOvUdrBOpTqVL.axPCsBvgcqYmqC8j8jdWQwEoMCM937a', 'Devon Clarke',   'Associate',             'staff',     2,    'active', 'light', '2026-07-12 15:58:00', '2025-06-16 09:00:00'),
(9, 'riley.chen@playground.demo',   '$2y$12$ZAz6GGzsbOvUdrBOpTqVL.axPCsBvgcqYmqC8j8jdWQwEoMCM937a', 'Riley Chen',     'Intern',                'intern',    3,    'active', 'light', '2026-07-15 13:15:00', '2026-05-04 09:00:00'),
(10,'morgan.ellis@playground.demo', '$2y$12$ZAz6GGzsbOvUdrBOpTqVL.axPCsBvgcqYmqC8j8jdWQwEoMCM937a', 'Morgan Ellis',   'Client Relations Lead', 'crm_team',  NULL, 'active', 'light', '2026-07-14 08:50:00', '2025-08-11 09:00:00');

-- ---------------------------------------------------------------------
-- Role permissions (shows off the View vs Manage tier split)
-- ---------------------------------------------------------------------
INSERT INTO role_permissions (role, manage_employees, view_employees, manage_clients_engagements, view_clients_engagements, view_master_schedule, manage_master_schedule, approve_time_off, view_time_off_requests, access_system_settings) VALUES
('manager',   1, 1, 1, 1, 1, 1, 1, 1, 0),
('senior',    0, 1, 0, 1, 1, 0, 0, 1, 0),
('staff',     0, 0, 0, 1, 1, 0, 0, 0, 0),
('intern',    0, 0, 0, 1, 1, 0, 0, 0, 0),
('crm_team',  0, 1, 1, 1, 0, 0, 0, 0, 0);

-- ---------------------------------------------------------------------
-- Clients
-- ---------------------------------------------------------------------
INSERT INTO clients (client_id, client_name, onboarded_date, status, notes) VALUES
(1, 'Brightline Logistics',    '2023-03-14', 'active',   'Long-standing freight/logistics client.'),
(2, 'Cedar Grove Retail',      '2023-08-02', 'active',   'Multi-location retail chain.'),
(3, 'Nimbus Health Partners',  '2024-01-22', 'active',   'Healthcare admin services group.'),
(4, 'Vantage Manufacturing',   '2024-05-10', 'active',   'Industrial equipment manufacturer.'),
(5, 'Solstice Media Group',    '2022-11-05', 'inactive', 'Engagement wound down in 2023.'),
(6, 'Harbor & Finch Law',      '2025-02-18', 'active',   'Boutique corporate law firm.'),
(7, 'Pinecrest Realty',        '2025-06-30', 'active',   'Commercial real estate brokerage.'),
(8, 'Aurora Biotech',          '2026-01-09', 'active',   'Newest client, onboarded this year.');

-- ---------------------------------------------------------------------
-- Engagements (current year, mixed statuses/utilization for demo variety)
-- ---------------------------------------------------------------------
INSERT INTO engagements (engagement_id, client_id, client_name, year, budgeted_hours, assigned_hours, manager, status, notes) VALUES
(1, 1, 'Brightline Logistics',   2026, 400, 346, 'Jordan Reyes', 'confirmed',     'Annual audit + advisory retainer.'),
(2, 2, 'Cedar Grove Retail',     2026, 250, 195, 'Casey Nguyen', 'confirmed',     'Quarterly close support.'),
(3, 3, 'Nimbus Health Partners', 2026, 600, 420, 'Jordan Reyes', 'pending',       'Awaiting signed SOW for full scope.'),
(4, 3, 'Nimbus Health Partners', 2026, 150, 120, 'Casey Nguyen', 'not_confirmed', 'Potential special project, not yet greenlit.'),
(5, 4, 'Vantage Manufacturing',  2026, 320, 360, 'Casey Nguyen', 'not_confirmed', 'Scope creep - running over initial budget.'),
(6, 6, 'Harbor & Finch Law',     2026, 180, 150, 'Jordan Reyes', 'confirmed',     'Trust accounting support.'),
(7, 7, 'Pinecrest Realty',       2026, 150, 120, 'Casey Nguyen', 'pending',       'New client, engagement letter out for signature.'),
(8, 8, 'Aurora Biotech',         2026, 500, 480, 'Jordan Reyes', 'confirmed',     'Full-scope engagement, ramping fast.'),
(9, 2, 'Cedar Grove Retail',     2026, 100, 45,  'Jordan Reyes', 'confirmed',     'Inventory system migration support.');

-- ---------------------------------------------------------------------
-- Entries (weekly staffing hours per engagement)
-- ---------------------------------------------------------------------
INSERT INTO entries (user_id, engagement_id, week_start, assigned_hours) VALUES
(4,1,'2026-06-22',65),(4,1,'2026-06-29',55),
(6,1,'2026-06-22',65),(6,1,'2026-06-29',55),
(8,1,'2026-06-22',55),(8,1,'2026-06-29',51),

(5,2,'2026-06-22',50),(5,2,'2026-06-29',40),
(7,2,'2026-06-22',55),(7,2,'2026-06-29',50),

(4,3,'2026-06-22',65),(4,3,'2026-06-29',55),
(6,3,'2026-06-22',65),(6,3,'2026-06-29',55),
(8,3,'2026-06-22',65),(8,3,'2026-06-29',55),
(9,3,'2026-06-22',35),(9,3,'2026-06-29',25),

(7,4,'2026-06-22',35),(7,4,'2026-06-29',25),
(9,4,'2026-06-22',35),(9,4,'2026-06-29',25),

(5,5,'2026-06-22',65),(5,5,'2026-06-29',55),
(6,5,'2026-06-22',65),(6,5,'2026-06-29',55),
(8,5,'2026-06-22',65),(8,5,'2026-06-29',55),

(4,6,'2026-06-22',50),(4,6,'2026-06-29',40),
(7,6,'2026-06-22',35),(7,6,'2026-06-29',25),

(5,7,'2026-06-22',40),(5,7,'2026-06-29',35),
(9,7,'2026-06-22',25),(9,7,'2026-06-29',20),

(4,8,'2026-06-22',65),(4,8,'2026-06-29',55),
(6,8,'2026-06-22',65),(6,8,'2026-06-29',55),
(8,8,'2026-06-22',65),(8,8,'2026-06-29',55),
(7,8,'2026-06-22',65),(7,8,'2026-06-29',55),

(9,9,'2026-06-22',25),(9,9,'2026-06-29',20);

-- ---------------------------------------------------------------------
-- Archived engagement history (for the collapsible year-group UI)
-- ---------------------------------------------------------------------
INSERT INTO client_engagement_history (client_id, engagement_year, budgeted_hours, allocated_hours, manager, senior, staff, notes, archived_by, archive_date) VALUES
(1, 2024, 350, 340, 'Jordan Reyes', 'Priya Shah',  'Taylor Kim, Devon Clarke', 'Year closed on schedule.',              'Alex Morgan', '2025-01-15 10:00:00'),
(1, 2025, 380, 375, 'Jordan Reyes', 'Priya Shah',  'Taylor Kim',               NULL,                                     'Alex Morgan', '2026-01-12 10:00:00'),
(2, 2024, 220, 210, 'Casey Nguyen', 'Marcus Webb', 'Sam Rivera',               NULL,                                     'Alex Morgan', '2025-01-20 10:00:00'),
(5, 2023, 200, 190, 'Jordan Reyes', 'Marcus Webb', 'Sam Rivera, Riley Chen',   'Engagement wound down; client went inactive.', 'Alex Morgan', '2024-01-08 10:00:00');

-- ---------------------------------------------------------------------
-- Time off (mix of statuses/categories) + a few global holidays
-- ---------------------------------------------------------------------
INSERT INTO time_off (user_id, request_group, category, week_start, holiday_date, assigned_hours, status, reviewed_by, reviewed_at, reviewer_comment, is_global_timeoff, timeoff_note) VALUES
(6, 'a1b2c3d4-0001-47a8-9b1c-000000000001', 'vacation',  '2026-07-13', NULL, 40, 'approved',          2, '2026-07-01 09:00:00', NULL, 0, NULL),
(7, 'a1b2c3d4-0002-47a8-9b1c-000000000002', 'sick',      '2026-07-06', NULL, 8,  'approved',          3, '2026-07-06 08:15:00', NULL, 0, NULL),
(4, 'a1b2c3d4-0003-47a8-9b1c-000000000003', 'vacation',  '2026-08-03', NULL, 40, 'pending',           NULL, NULL, NULL, 0, NULL),
(8, 'a1b2c3d4-0004-47a8-9b1c-000000000004', 'parental',  '2026-09-01', NULL, 40, 'pending',           NULL, NULL, NULL, 0, NULL),
(9, 'a1b2c3d4-0005-47a8-9b1c-000000000005', 'volunteer', '2026-07-20', NULL, 8,  'changes_requested', 3, '2026-07-10 13:40:00', 'Can you confirm which day - your engagement has a deadline that week?', 0, NULL),
(5, 'a1b2c3d4-0006-47a8-9b1c-000000000006', 'vacation',  '2026-07-27', NULL, 40, 'denied',            2, '2026-07-11 09:20:00', 'Please resubmit for a different week - overlaps with the Vantage close-out.', 0, NULL),
(7, 'a1b2c3d4-0007-47a8-9b1c-000000000007', 'vacation',  '2026-08-10', NULL, 24, 'approved',          3, '2026-07-02 09:00:00', NULL, 0, NULL),
(6, 'a1b2c3d4-0008-47a8-9b1c-000000000008', 'sick',      '2026-06-15', NULL, 8,  'approved',          2, '2026-06-15 08:05:00', NULL, 0, NULL),
(NULL, NULL, 'vacation', '2026-06-29', '2026-07-04', 8, 'approved', NULL, NULL, NULL, 1, 'Independence Day'),
(NULL, NULL, 'vacation', '2026-09-07', '2026-09-07', 8, 'approved', NULL, NULL, NULL, 1, 'Labor Day'),
(NULL, NULL, 'vacation', '2026-11-23', '2026-11-26', 8, 'approved', NULL, NULL, NULL, 1, 'Thanksgiving Day');

INSERT INTO time_off_comments (request_group, user_id, comment, created) VALUES
('a1b2c3d4-0005-47a8-9b1c-000000000005', 3, 'Can you confirm which day - your engagement has a deadline that week?', '2026-07-10 13:40:00'),
('a1b2c3d4-0005-47a8-9b1c-000000000005', 9, 'Good catch - I can move it to Friday the 24th instead.', '2026-07-10 15:02:00'),
('a1b2c3d4-0006-47a8-9b1c-000000000006', 5, 'Understood, resubmitting for the following week.', '2026-07-11 10:00:00');

-- ---------------------------------------------------------------------
-- Policies / memos
-- ---------------------------------------------------------------------
INSERT INTO policies (title, doc_type, source_type, effective_date, memo_to, memo_from, content, created_by, updated_by) VALUES
('Remote Work Policy', 'policy', 'editor', '2026-01-01', NULL, NULL, '<p>Employees may work remotely up to three days per week with manager approval. Core collaboration hours are 10am-3pm local time.</p>', 1, 1),
('Time Off Request Guidelines', 'policy', 'editor', '2026-01-01', NULL, NULL, '<p>Submit time off requests at least two weeks in advance where possible. Sick leave may be submitted after the fact.</p>', 1, 1),
('Q3 Client Onboarding Update', 'memo', 'editor', '2026-07-01', 'All Staff', 'Alex Morgan', '<p>We are excited to welcome Aurora Biotech and Pinecrest Realty this year. Please review staffing assignments in Master Schedule.</p>', 1, 1);

-- ---------------------------------------------------------------------
-- System settings (so Backup/Security/Email modals aren't empty)
-- ---------------------------------------------------------------------
INSERT INTO settings (setting_master_key, setting_key, setting_value) VALUES
('backup_config', 'enable_automated_backups', 'true'),
('backup_config', 'backup_frequency', 'daily'),
('backup_config', 'backup_time', '02:00'),
('backup_config', 'local_backup_directory', '/var/www/client-scheduler-demo/storage/backups'),
('backup_config', 'retention_period_days', '14'),
('security_policy', 'min_password_length', '8'),
('security_policy', 'require_uppercase', 'true'),
('security_policy', 'require_lowercase', 'true'),
('security_policy', 'require_numbers', 'true'),
('security_policy', 'require_symbols', 'false'),
('security_policy', 'password_expiration_days', '0'),
('security_policy', 'max_login_attempts', '5'),
('security_policy', 'lockout_duration_minutes', '15'),
('security_policy', 'session_timeout_minutes', '60'),
('security_policy', 'api_rate_limit_per_minute', '120'),
('email', 'enable_email_notifications', 'false'),
('email', 'notification_frequency', 'daily_digest'),
('email', 'smtp_server', 'smtp.playground-demo.test'),
('email', 'smtp_port', '587'),
('email', 'smtp_username', 'demo@playground-demo.test'),
('email', 'smtp_password', ''),
('email', 'sender_name', 'AARC-360 Playground'),
('email', 'sender_email', 'no-reply@playground-demo.test');

-- ---------------------------------------------------------------------
-- Activity log + backup history (cosmetic, for realism)
-- ---------------------------------------------------------------------
INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description, created_at) VALUES
('login', 1, 'admin@playground.demo', 'Alex Morgan', 'Login', 'Successful login', '2026-07-15 17:40:00'),
('client_added', 2, 'jordan.reyes@playground.demo', 'Jordan Reyes', 'Client Added', 'Added client Aurora Biotech', '2026-01-09 10:12:00'),
('engagement_confirmed', 1, 'admin@playground.demo', 'Alex Morgan', 'Engagement Confirmed', 'Confirmed engagement for Brightline Logistics', '2026-01-20 11:00:00'),
('time_off_approved', 2, 'jordan.reyes@playground.demo', 'Jordan Reyes', 'Time Off Approved', 'Approved Taylor Kim vacation request', '2026-07-01 09:00:00'),
('time_off_denied', 2, 'jordan.reyes@playground.demo', 'Jordan Reyes', 'Time Off Denied', 'Denied Marcus Webb vacation request', '2026-07-11 09:20:00'),
('settings_updated', 1, 'admin@playground.demo', 'Alex Morgan', 'Settings Updated', 'Updated email notification settings', '2026-06-01 14:00:00');

INSERT INTO backup_history (backup_file, backup_size, status, created_at) VALUES
('db_backup_2026-07-14_020000.sql', 4823112, 'success', '2026-07-14 02:00:12'),
('db_backup_2026-07-15_020000.sql', 4831560, 'success', '2026-07-15 02:00:09'),
('db_backup_2026-07-16_020000.sql', 4840217, 'success', '2026-07-16 02:00:14');
