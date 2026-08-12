# Phase 0-2 — Migration scripts

Scripts for folding Engagement Tracker's audit-tracking data into Client
Scheduler. See `docs/client-scheduler-migration-plan.md` in the Engagement
Tracker repo for the full plan.

## The two-server problem — read this first

**Engagement Tracker and Client Scheduler run on two different servers**,
each with its own MySQL instance that the other can't reach directly. Every
script in this folder needs to talk to *both* databases at once:

- The **Client Scheduler DB** — always the "local" one, via this repo's own
  `includes/db.php` (needs a real `.env`, same as running the app itself).
- The **Engagement Tracker DB** — always the "remote" one, via
  `connectSourceEngagementTracker()` in `lib.php`.

Reaching the remote one across servers means an **SSH tunnel** — never
open either database to the public internet for this. Two ways to run
these scripts:

**Option A (recommended) — run from your own laptop.** Nothing needs to be
deployed to either server. Open a tunnel to *each* database:

```bash
# Client Scheduler's DB, default MySQL port locally (nothing else is
# using 3306 on your laptop):
ssh -fN -L 3306:localhost:3306 <ssh-user>@<client-scheduler-server>

# Engagement Tracker's DB, a different local port since 3306 is already
# claimed by the tunnel above:
ssh -fN -L 3307:localhost:3306 <ssh-user>@<engagement-tracker-server>
```
(`-fN` backgrounds the tunnel with no remote command; `killall ssh` or find
the process and kill it when you're done. If either server's MySQL is
already reachable directly on your network, you don't need that half of
the tunnel — just point the host/port at it instead.)

Then create two **local, gitignored** files (never commit real credentials):

`FSD - Client Scheduler/.env` (this repo's own root — same file the live
app itself reads):
```
DB_HOST=127.0.0.1
DB_USER=<client scheduler's real DB user>
DB_PASSWORD=<...>
DB_NAME=<...>
```

`FSD - Client Scheduler/tools/audit-migration/.env.et`:
```
DB_HOST=127.0.0.1
DB_PORT=3307
DB_USER=<engagement tracker's real DB user>
DB_PASSWORD=<...>
DB_NAME=<...>
```

**Option B — run directly on the Client Scheduler server.** Its own
`includes/db.php` already has the real production `.env`, so only *one*
tunnel is needed (from the Client Scheduler server, out to Engagement
Tracker's DB) — set `ET_DB_HOST=127.0.0.1` / `ET_DB_PORT=<tunnel port>` as
real environment variables there, or its own `.env.et` file in this folder.
This touches production directly, so prefer Option A while still
iterating; Option B is reasonable once you trust the numbers a dry run
gives you.

Either way: nothing in this folder ever needs Engagement Tracker's server
to reach out to Client Scheduler, or vice versa — the connection only ever
goes outward from wherever these scripts run.

## What each script does, in order

0. **`0-apply-schema.php`** — applies
   `storage/migrations/2026-08-12_add_audit_tracking_schema.sql` to Client
   Scheduler's DB. Client Scheduler connection only — no tunnel to
   Engagement Tracker needed for this step. Run once; re-running errors out
   on already-existing tables/columns (that's the "already done" signal).
1. **`1-identity-crosswalk.php`** — matches every name in Engagement
   Tracker's `employees` roster (plus any stray free-text name on
   `engagement_team` that never made it into the roster) against Client
   Scheduler's real `users` table. Read-only on both sides.
2. **`2-engagement-crosswalk.php`** — matches every Engagement Tracker
   engagement (active *and* archived — all of it) to a Client Scheduler
   `client_id` + the right year's `engagement_id`. Read-only on both sides.
3. **`3-backfill-missing-clients.php`** — as-needed. If Client Scheduler's
   `clients` table doesn't have most of Engagement Tracker's roster yet
   (expected if it hasn't been rolled out company-wide), script 2 will
   show a lot of "no client match" rows — not one-off typos, a real gap.
   This creates the missing `clients` + `engagements` rows in bulk instead
   of hand-editing dozens of CSV cells. Client Scheduler connection only.
   Safe by default (transaction, rolls back unless `--commit`). **After
   running with `--commit`, re-run script 2** — those rows should come
   back as exact matches.
4. **`4-migrate-data.php`** — reads the (by then resolved) crosswalk CSVs
   and copies the actual data across. Safe by default: runs in a
   transaction and rolls back unless you pass `--commit`.

Scripts 1 and 2 **never auto-resolve an ambiguous or missing match** — they
classify each row as `exact`, `fuzzy - needs confirmation`, or `no match`
(or the engagement-specific equivalents) and write two files:

- `output/*_<timestamp>.csv` — the full report, every row.
- `output/*_UNMATCHED_<timestamp>.csv` — only the rows that weren't an
  exact match. **This file is the required review artifact** — per the
  migration plan, every row on it needs to be resolved (fix the match, or
  explicitly confirm it's new/excluded and why) before script 4 is allowed
  to run for real.

`output/` is gitignored — these reports contain real names and are not
meant to be committed.

## Running, in order

From the Client Scheduler project root (wherever you set up the tunnels
above):

```bash
php tools/audit-migration/0-apply-schema.php
php tools/audit-migration/1-identity-crosswalk.php
php tools/audit-migration/2-engagement-crosswalk.php
```

Each of the crosswalk scripts prints a short summary (total checked /
exact matches / needs review) and the paths of the two CSVs it wrote.

### If the engagement crosswalk shows lots of "no client match"

That's expected if Client Scheduler hasn't been rolled out yet and its
`clients` table is mostly empty — not dozens of individual typos. Backfill
in bulk, then re-check:

```bash
php tools/audit-migration/3-backfill-missing-clients.php              # dry run first
php tools/audit-migration/3-backfill-missing-clients.php --commit      # then for real
php tools/audit-migration/2-engagement-crosswalk.php                  # re-run — should mostly be exact now
```

### Then, before going further

1. Open both `output/*_UNMATCHED_*.csv` files.
2. For every row: either it's a genuine near-miss (fix a nickname/typo,
   fill in the correct `cs_user_id` / `cs_engagement_id` directly in the
   **full** report CSV — not the UNMATCHED one, that's just the filtered
   view — and re-run script 4 against it), or it's genuinely new (create
   it by hand, or via script 3 above for engagements), or it's something
   that should be excluded — leave its `cs_user_id`/`cs_engagement_id`
   blank and note why.
3. Once every row is accounted for:

```bash
php tools/audit-migration/4-migrate-data.php              # dry run first
php tools/audit-migration/4-migrate-data.php --commit      # then for real
```

Read the migration log (`output/migration_log_*.csv`) after the dry run —
it lists every row migrated, skipped, or conflicting, before anything is
actually kept.
