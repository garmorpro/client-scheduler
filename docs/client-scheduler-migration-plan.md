# Folding Engagement Tracker into Client Scheduler (AARC-360) — Phased Plan

> **Mirror copy.** The canonical version of this doc lives in the Engagement Tracker repo at `docs/client-scheduler-migration-plan.md` (also published as a Claude Artifact: https://claude.ai/code/artifact/13ffdb63-75e1-4910-ad38-1f6706c121a2). This copy was placed here on 2026-08-13 so it's readable directly from the Client Scheduler repo/laptop without needing the other repo checked out too. Update both when this changes, or treat Engagement Tracker's copy as the source of truth if they ever drift.

**Status (2026-08-13):** Phases 0-2 are live in production (schema applied, all 21 engagements migrated, verified). Phase 3 (UI) is done through step 6 (notifications) — only step 5 (restyle, deliberately last/cosmetic) remains. All work sits on branch `audit-tracking-migration` in the Client Scheduler repo (github.com/garmorpro/client-scheduler), pushed but not yet merged to `main`.

## 📍 Pick up here — loose ends from the last session

These are the specific things left mid-flight, not yet fully closed out:

1. ~~**AARC-360 logo file still needs to be added.**~~ **Done (2026-08-13).** The real logo (`assets/images/aarc-360-logo-1.webp`, already tracked in this repo) was converted to PNG and saved at `assets/images/aarc360-logo.png` — the exact path `includes/email_functions.php` expects. Converted to PNG (not left as webp) since Outlook and other email clients don't reliably render webp in embedded images. No code changes needed — `wrapBrandedEmailHtml()` already checks `file_exists()` on this path and will pick it up automatically. Next real send (e.g. `sudo php pages/audit-notification-cron.php --force`) should show the actual logo instead of the "AARC-360" text fallback.
2. **SMTP is configured with personal Gmail credentials for testing** (`garrett.morgan.pro@gmail.com`, via an App Password), not the real Microsoft 365 relay — deliberate, Garrett said to worry about swapping in the real M365 config later. Whoever picks this up should know the *current* sender is a personal Gmail, not the firm's real email.
3. **A real test due-date is sitting in the database** — `audit_engagement_timeline.final_report_date` was manually set to "tomorrow" on whichever engagement `SELECT DISTINCT engagement_id FROM entries LIMIT 1` returns, purely to exercise the notification pipeline. Worth clearing/resetting once done testing, since it's not a real due date — it'll otherwise sit there as fake data on a real (migrated) engagement.
4. **Audit Notification Schedule is live and enabled** — configured via Settings → Audit Notification Schedule, which actually rewrites the server crontab (under the `www-data` OS user, not any human user's crontab — see `sudo crontab -u www-data -l` to inspect it). Currently set to weekdays only, with the send time last saved. This means **real digest emails will start going out on schedule** — worth confirming the schedule/enabled state is actually what's wanted before walking away from this.
5. **A real, subtle bug was found and fixed this session**: the due-date "days until" math compared the exact current moment against midnight of the due date instead of calendar-day-to-calendar-day, causing anything due "tomorrow" to be silently skipped if the cron happened to run in the afternoon/evening. Fixed in `includes/audit_notifications.php` (`auditDaysUntil()`) — this bug likely also exists in Engagement Tracker's own `notification-helper.php` (same math, inherited from there), never fixed on that side since it wasn't caught until this testing.
6. **Two pre-existing bugs unrelated to this migration were also found and fixed**: (a) Email/Backup/Security settings forms in `service-settings.php` always rendered empty on load regardless of what was saved (a missing `$settings` variable, not a saving bug); (b) a dead "Notification Frequency" dropdown in the Email settings modal that nothing ever read, replaced with a pointer to the real Audit Notification Schedule.
7. ~~**A now-orphaned Bootstrap "Add Engagement" modal** (`includes/modals/add_engagement_modal.php` + `assets/js/add_engagement_modal.js`) is still sitting in the codebase, confirmed dead/unreachable — flagged as a background cleanup task, not yet done.~~ **Done.** Confirmed via repo-wide grep that nothing referenced `data-bs-target="#addEngagementModal"` — the live `.add-engagement-btn` flow is `assets/js/swal-modals/add-engagement-modal.js` instead. Deleted both files and their two include/script lines in `client-management.php`.

## Decisions locked in

## Decisions locked in

The four schema-relevant open questions have been answered. These are now treated as final for Phase 1, not options:

1. **Team membership derives from `entries`.** No new "who's on this engagement" table — see below.
2. **Everything migrates**, archived Engagement Tracker engagements included, not just active ones. The Phase 0 crosswalk must produce a visible list of anything that doesn't find a confident match, so it can be resolved by hand rather than silently dropped.
3. **Notifications move to Client Scheduler's existing PHPMailer/email infrastructure.** Slack/ntfy are not part of the new system's design (Engagement Tracker's own instance keeps them until it's decommissioned).
4. **Permissions split three ways, not two:** DOL editing, timeline *date* editing, and timeline *completion* are three separate grants — see Phase 1.

Only one open question remains: decommission timing for Engagement Tracker after cutover (Phase 4). Not blocking — flagged at the bottom.

**Post-migration decision (2026-08-12):** audit type + TSC get set **at engagement creation**, by whoever adds it (Manager/CRM Team/Admin) — not later by an assigned Senior during some future onboarding flow. Audit type selection already existed on Client Scheduler's Add/Edit Engagement forms; TSC (SOC 2-specific) was added to both, shown only when SOC 2 is checked. Reasoning: Master Schedule already lets people log hours against a specific `audit_type_id` per entry, and the DOL Generator needs TSC for SOC 2 criteria derivation — both would be blocked from day one if this waited for staffing. Also avoids building an onboarding-task system that doesn't exist yet just to enable this.

**Infrastructure note:** Engagement Tracker and Client Scheduler run on two separate servers with two separate MySQL instances — there's no shared database for the migration tooling to sit inside. Every script that needs both (Phase 0's crosswalks, Phase 2's data migration) reaches the "remote" one over an SSH tunnel; neither database gets exposed publicly. See `tools/audit-migration/README.md` in the Client Scheduler repo for the exact setup.

## The core decision

Client Scheduler already owns `clients` and `engagements`. Engagement Tracker doesn't get ported over as its own parallel set of tables — it becomes a **new set of tables that hang off Client Scheduler's existing `engagements` row**, one-to-one or one-to-many by `engagement_id`. Client Scheduler keeps doing what it already does (capacity planning: budgeted vs. assigned hours, weekly staffing via `entries`); the new tables add the audit-lifecycle layer on top (timeline, milestones, DOL down to the criteria level, independence, training restrictions).

Two systems' idea of "engagement" turn out to line up well enough to support this:

| | Client Scheduler `engagements` | Engagement Tracker `engagements` |
|---|---|---|
| Granularity | One row per client, roughly per year | One row per audit cycle |
| Multi-audit-type support | Yes — `engagement_audit_types` junction, multi-select | Yes — `eng_audit_type` comma list |
| Repeats | `year` column, new row each year | `eng_repeat` flag, no structured renewal yet |

Both already model "one engagement can cover more than one audit framework." The mismatch is that Client Scheduler's engagement stops at *which* audit types are in scope; Engagement Tracker goes further into *when things are due* and *who's doing which criteria*. That's exactly the layer this plan adds.

## What's reused as-is (no schema changes)

- `clients`
- `engagements`
- `audit_types` / `engagement_audit_types`
- `users` (real logins, `manager_id` hierarchy, `role` column)
- `role_permissions` (the `manage_X` / `view_X` matrix, `manage_X` implies `view_X`)
- `entries` (weekly hours per person per engagement per audit type — capacity planning, left untouched, and now also **doubles as the team-membership record**: "who's on this engagement" is answered by `SELECT DISTINCT user_id FROM entries WHERE engagement_id = ?`, not a separate table — see below)
- `system_activity_log` (already the same shape as the `activity_log` table built into Engagement Tracker this session — new event types write into this instead of a separate table)

## New tables

All keyed to Client Scheduler's existing `engagements.engagement_id` and, where relevant, `users.user_id` and `audit_types.audit_type_id` — no engagement or client data gets duplicated.

- **`audit_engagement_details`** (1:1 with `engagements`) — the audit-specific fields Client Scheduler's `engagements` doesn't have: location, POC, TSC, SOC type (I/II), scope, as-of date, review period start/end, report type, repeat flag, notes, planning doc reference.
- **`audit_engagement_timeline`** (1:1 with `engagements`) — every key date Engagement Tracker tracks today, including the fieldwork start/end ranges and the weekly status call (day + link group + group name), each with its paired `_completed_at`.
- **`audit_engagement_milestones`** (1:many) — custom milestones per engagement, same shape as today.
- **`audit_dol_assignments`** (1:many) — one row per person + audit type + criterion (`engagement_id`, `user_id`, `audit_type_id`, `criterion`), replacing Engagement Tracker's comma-separated `emp_soc2_dol`-style columns with a real relational table. This is a genuine upgrade, not just a port.
- **`audit_team_independence`** (1:many, one row per person per engagement) — the check/X/unanswered attestation, unchanged in spirit from what's live in Engagement Tracker today, just re-keyed to a real `user_id` instead of a free-text name.
- **`dol_training_restrictions`** (many:many, `user_id` + `criterion`) — replaces the `employees.emp_restricted_criteria` comma column with a proper junction table. Because it's keyed to a real `user_id`, this is also the piece that makes self-service natural later — no name-matching required.

No separate "team members" table. **Decided:** who's staffed on an engagement is derived from `entries` (anyone with at least one hours row for that `engagement_id`) rather than tracked as its own explicit assignment. Two things fall out of this:
- Someone must have an `entries` row before they can be given a DOL assignment or asked to attest independence on that engagement — in practice this is a non-issue since staffing (via `entries`) is how someone gets put on an engagement in Client Scheduler in the first place, but note it if a workflow ever needs to assign DOL *before* a week's hours have been scheduled.
- `audit_dol_assignments` and `audit_team_independence` still store their own `user_id` FK directly (not a join through `entries`) — they don't require an `entries` row to exist at write time, just conventionally track alongside one. No enforced constraint ties them together; it's a workflow assumption, not a database rule.

## Schema diagram

```mermaid
erDiagram
    CLIENTS ||--o{ ENGAGEMENTS : has
    ENGAGEMENTS ||--o{ ENGAGEMENT_AUDIT_TYPES : covers
    AUDIT_TYPES ||--o{ ENGAGEMENT_AUDIT_TYPES : "selected via"
    ENGAGEMENTS ||--o{ ENTRIES : "weekly hours + team membership"
    USERS ||--o{ ENTRIES : staffs
    AUDIT_TYPES ||--o{ ENTRIES : "worked on"
    USERS ||--o{ ROLE_PERMISSIONS : "role grants"

    ENGAGEMENTS ||--|| AUDIT_ENGAGEMENT_DETAILS : "extends (new)"
    ENGAGEMENTS ||--|| AUDIT_ENGAGEMENT_TIMELINE : "extends (new)"
    ENGAGEMENTS ||--o{ AUDIT_ENGAGEMENT_MILESTONES : "has (new)"
    ENGAGEMENTS ||--o{ AUDIT_DOL_ASSIGNMENTS : "splits into (new)"
    USERS ||--o{ AUDIT_DOL_ASSIGNMENTS : "responsible for (new)"
    AUDIT_TYPES ||--o{ AUDIT_DOL_ASSIGNMENTS : "criteria under (new)"
    ENGAGEMENTS ||--o{ AUDIT_TEAM_INDEPENDENCE : "attested on (new)"
    USERS ||--o{ AUDIT_TEAM_INDEPENDENCE : attests
    USERS ||--o{ DOL_TRAINING_RESTRICTIONS : "restricted from (new)"

    CLIENTS {
        int client_id PK
        string client_name
        string status
    }
    ENGAGEMENTS {
        int engagement_id PK
        int client_id FK
        int year
        string status
    }
    AUDIT_TYPES {
        int audit_type_id PK
        string name
        string color
    }
    USERS {
        int user_id PK
        string full_name
        string email
        string role
        int manager_id FK
    }
    AUDIT_ENGAGEMENT_DETAILS {
        int engagement_id PK_FK
        string location
        string poc
        string tsc
        string soc_type
        boolean repeat_flag
    }
    AUDIT_ENGAGEMENT_TIMELINE {
        int engagement_id PK_FK
        date internal_planning_call_date
        date fieldwork_client_calls_start_date
        date fieldwork_client_calls_end_date
        date final_report_date
        int weekly_status_call_day
        string weekly_status_call_group
    }
    AUDIT_ENGAGEMENT_MILESTONES {
        int milestone_id PK
        int engagement_id FK
        string milestone_type
        date due_date
        boolean is_completed
    }
    AUDIT_DOL_ASSIGNMENTS {
        int id PK
        int engagement_id FK
        int user_id FK
        int audit_type_id FK
        string criterion
    }
    AUDIT_TEAM_INDEPENDENCE {
        int engagement_id PK_FK
        int user_id PK_FK
        string independent
    }
    DOL_TRAINING_RESTRICTIONS {
        int user_id PK_FK
        string criterion PK
    }
```

## Phased plan

### Phase 0 — Reconciliation ✅ complete, run for real on ms-dev-01
1. **Done:** `tools/audit-migration/1-identity-crosswalk.php` — ran against live data. 24/24 names resolved (23 exact, 1 fuzzy match — "Shakeem Bryan" vs. Client Scheduler's "Shak Bryan" — manually confirmed as the same person; the role mismatch shown, senior vs. manager, just reflects that Client Scheduler has his more current title).
2. **Done:** `tools/audit-migration/2-engagement-crosswalk.php` — ran against live data. Initially only 4/21 engagements matched — **not typos, a real gap**: Client Scheduler hasn't been rolled out company-wide yet, so its `clients` table was missing most of Engagement Tracker's actual roster. Built `tools/audit-migration/3-backfill-missing-clients.php` to close that gap in bulk (creates the missing `clients` + `engagements` + `engagement_audit_types` rows, safe-by-default with a dry run), ran it with `--commit`, then re-ran the crosswalk: **21/21 exact.**
3. **Done:** the unmatched-log mechanism (`*_UNMATCHED.csv` per script) worked as designed — both files are now empty since every row resolved to a confident match.

### Phase 1 — Schema ✅ applied to ms-dev-01
1. **Done, live:** `storage/migrations/2026-08-12_add_audit_tracking_schema.sql` (Client Scheduler repo, branch `audit-tracking-migration`) has been applied to Client Scheduler's real database on ms-dev-01 via `tools/audit-migration/0-apply-schema.php`. All six new tables and the five `role_permissions` columns exist now. Two real-world fixes came out of actually running it, both committed: `users.user_id` is `INT UNSIGNED` on this server (the FK columns referencing it were fixed to match), and `ADD COLUMN IF NOT EXISTS` isn't supported pre-MySQL 8.0.29 (that one ALTER statement dropped the clause; re-run safety for it now lives in the PHP runner instead, which treats a duplicate-column error as already-applied).
2. Add to `role_permissions` — the DOL/timeline split is now three distinct grants, not two:
   - `manage_dol` — edit DOL assignments/splits. **Manager, Senior.**
   - `view_dol` — read-only DOL visibility. Implied by `manage_dol`; also granted directly to **Staff, Intern** so they can see their own assignments.
   - `manage_audit_timeline` — set/move the actual due dates (fieldwork ranges, final report date, weekly status call, etc.). **Manager, Senior.** Same tier as DOL editing, but kept as its own permission key rather than reusing `manage_dol`, since they're conceptually different actions even though the current role split happens to match.
   - `complete_audit_timeline_items` — check off / mark complete on an existing timeline item, **without** being able to move its date. **Manager, Senior, Staff, Intern** — anyone staffed on the engagement (i.e., anyone with an `entries` row for it) can do this. This is the permission that's broader than DOL/timeline-edit and is what makes "staff and interns can mark/complete items" possible without also giving them date-editing rights.
   - `view_audit_timeline` — implied by either `manage_audit_timeline` or `complete_audit_timeline_items`.
3. No changes to any existing table.

### Phase 2 — Data migration ✅ committed, live on ms-dev-01
1. **Done:** `tools/audit-migration/4-migrate-data.php` — dry run first (0 skipped, 0 conflicts), then re-run with `--commit`. All 21 engagements migrated cleanly: `audit_engagement_details`, `audit_engagement_timeline`, `audit_engagement_milestones` (where present), `audit_dol_assignments`, `audit_team_independence`, and one `dol_training_restrictions` row all populated. Real data, real production database — this is no longer a dry run or a draft.
2. Every row in both Phase 0 unmatched logs was resolved before this ran, per the plan's own requirement.
3. **Next:** spot-check a few engagements directly in Client Scheduler's UI (or a few `SELECT`s against the new tables) to eyeball that the data reads right before treating this as fully trusted.

### Phase 3 — UI (scoped 2026-08-12, based on reading the actual code — not just the earlier assumption)

Two corrections to how this was originally framed:
- **There's no dedicated engagement-details *page*.** `pages/engagement-details.php` is a 91-line JSON API endpoint, not a page — it feeds a shared **"View Engagement" modal** (`assets/js/view_engagement_modal.js`) reused across three pages: `my-schedule.php`, `client-management.php`, `engagement-management.php`. Extending the modal + its API once surfaces the new data everywhere, including "my engagements" (item 3 below) — no separate build needed for that.
- **The 5 new permission keys from Phase 1 aren't wired up yet.** `includes/permissions.php`'s `user_has_permission()` has a hardcoded `$allowed` whitelist, a fixed `SELECT` column list, and a fixed fallback array — none of them include `manage_dol` / `view_dol` / `manage_audit_timeline` / `complete_audit_timeline_items` / `view_audit_timeline` yet. Right now those columns exist and are populated in the DB, but every permission check against them silently returns `false`. This has to be step 1, or nothing gated behind them will work.

Sequenced:
1. **Permissions plumbing ✅ done, both halves.** Backend: added the 5 keys to `permissions.php`'s whitelist, `SELECT`, and fallback array; wired `view_dol` implied-by-`manage_dol` and `view_audit_timeline` implied-by-(`manage_audit_timeline` OR `complete_audit_timeline_items`). Admin UI: the Role Permissions matrix modal (`role-permissions.php` / `get_role_permissions.php` / `update_role_permissions.php` / `role_permissions.js`) didn't know about the new columns either — the only path to changing them was a direct DB edit. Added DOL as a normal view/manage pair and Audit Timeline as a genuine 3-tier group (view/complete/manage) with its own sync logic, since `complete_audit_timeline_items` is deliberately broader than `manage_audit_timeline` and the existing pair-sync would have two "manage" sources fight over one "view" toggle.
2. **Engagement detail surface ✅ done.** Extended `pages/engagement-details.php`'s JSON response with an `audit` key (timeline incl. fieldwork ranges/weekly call, milestones, DOL grouped by person, independence), gated server-side by `view_audit_timeline`/`view_dol`. Extended `view_engagement_modal.js` with 4 new render functions and matching CSS (reuses the existing `eng-vm-*` neutral-card conventions). Widened the modal (`modal-md` → `modal-lg`) for the extra content. Live on all three pages that share this modal: `my-schedule.php`, `client-management.php`, `engagement-management.php`.
3. **DOL Generator ✅ done, two parts.** Scope grew during this step: Garrett wants a proper Training page too (`dol_training_restrictions` had zero UI, same gap as the Role Permissions matrix), so it split into:
   - **Training page** (`pages/training.php`, new feature, not a port) — Manager/Senior (`manage_dol`) edit anyone's training restrictions; Staff/Intern (`view_dol`) see only their own, read-only.
   - **DOL Generator port** (`pages/dol-generator.php`) — ported from Engagement Tracker's *last committed* version (the on-disk one there has restrictions/manual-swap stripped out, uncommitted, unexplained — ported from git history per explicit direction). Algorithm unchanged, verified offline post-port (restriction avoidance, proportional weighting, "assign anyway + flag" fallback all correct). Real differences from the source: hours prefill from actual `entries.assigned_hours` instead of always zero; restrictions are read-only here now (edited on the Training page instead); save is one atomic transaction to `save-dol-assignments.php` instead of N sequential per-person requests; team eligibility follows the `entries`-based membership decision from Phase 0.
   - Styling is a functional first pass (AARC-360 tokens, not a full custom design system) — Garrett is revisiting page design separately later.
4. **"My engagements"** — mostly falls out of step 2 for free, since `my-schedule.php` already uses the shared modal. The remaining piece: timeline items checkable by anyone holding `complete_audit_timeline_items`, due dates editable only by `manage_audit_timeline`, DOL visible read-only under `view_dol`.
5. **Calendar ✅ built, currently 🔒 hidden.** Evaluated the "extend master-schedule.php?" question properly: it's a weekly hours-*grid* (rows=employees, columns=weeks) — a different UI paradigm from a month calendar of due dates. Built `pages/audit-calendar.php` as its own page instead, ported from Engagement Tracker's `calendar.php`/`getCalendarItemsForMonth()` (spanning fieldwork-range bars, linked weekly-call merging). Clicking an item opens the existing View Engagement modal in place, since Client Scheduler has no full-page engagement view to navigate to like Engagement Tracker's dashboard drawer had. **Deliberately hidden from everyone, including admin, as of 2026-08-12** — Garrett likes it but doesn't want it live yet. Fully built and working, just gated off two ways (page hard-redirects; sidebar link wrapped in `if (false)`), both marked `AUDIT_CALENDAR_DISABLED` — grep that string in the Client Scheduler repo to find and re-enable both spots in one pass.
6. Restyle to AARC-360's palette (`#003f47` / `#a3cc38`) — cosmetic, do last.
7. **Notifications ✅ done.** Ported Engagement Tracker's `checkUpcomingKeyDates()`/`checkUpcomingMilestones()` (same 1-7/5-day windows, same notify-once dedup, same bundling of multiple due items). One real design fork resolved explicitly by Garrett: ET broadcast one message per engagement to a single shared Slack/ntfy channel — this instead targets each engagement's actual team (via `entries`) with real emails, grouped **by recipient** (one combined digest per person covering every engagement they're on, not N separate emails). Reuses Client Scheduler's existing PHPMailer wiring (`includes/email_functions.php`) rather than new send infrastructure. New dedup table (`audit_notification_log`) and CLI cron entrypoint (`pages/audit-notification-cron.php`). Slack/ntfy stay on Engagement Tracker's own instance only, not carried into the new build.
   - **Follow-up, also done:** the cron schedule itself is now editable from Settings → Audit Notification Schedule, which rewrites the server crontab directly (not just a DB toggle) — Garrett's explicit choice over the safer "fixed time + DB flag" default. Real capability increase over the rest of this app (a web page can now modify what runs on the OS), built carefully: strict hour/minute allowlist before anything touches a shell command, `crontab <tempfile>` never a single interpolated string, a unique marker comment so only this feature's own line is ever touched (every other cron job on the server is preserved), and the OS user actually affected is surfaced back in the UI. Verified the line-rewrite logic offline (fresh install / time-update-preserves-other-jobs / disable-removes-only-ours) before shipping.

### Phase 4 — Cutover
1. Run both systems in parallel for a defined window (a couple of weeks of a live engagement cycle, not just a spot check) before treating Client Scheduler as the system of record.
2. Once confirmed, stop updating Engagement Tracker. Keep its database as a read-only archive rather than deleting it outright.

## Open question — resolved

- **Decommission timeline for Engagement Tracker:** not yet, indefinitely. Engagement Tracker keeps running as the live system alongside Client Scheduler; Phase 4's "stop updating it, keep it read-only" step doesn't happen on any set date. Revisit once Client Scheduler's audit-tracking layer has actually been in real use for a while.

All five open questions are now answered — nothing left blocking any phase.
