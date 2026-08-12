# Phase 0 — Reconciliation scripts

Read-only crosswalk scripts for folding Engagement Tracker's audit-tracking
data into Client Scheduler. Neither script writes to either database — they
only read from both and produce CSV reports for a human to review. See
`docs/client-scheduler-migration-plan.md` in the Engagement Tracker repo for
the full plan.

## What these do

1. **`1-identity-crosswalk.php`** — matches every name in Engagement
   Tracker's `employees` roster (plus any stray free-text name on
   `engagement_team` that never made it into the roster) against Client
   Scheduler's real `users` table.
2. **`2-engagement-crosswalk.php`** — matches every Engagement Tracker
   engagement (active *and* archived — all of it) to a Client Scheduler
   `client_id` + the right year's `engagement_id`.

Both scripts are **read-only** and **never auto-resolve an ambiguous or
missing match** — they classify each row as `exact`, `fuzzy - needs
confirmation`, or `no match` (or the engagement-specific equivalents) and
write two files:

- `output/*_<timestamp>.csv` — the full report, every row.
- `output/*_UNMATCHED_<timestamp>.csv` — only the rows that weren't an
  exact match. **This file is the required review artifact** — per the
  migration plan, every row on it needs to be resolved (fix the match, or
  explicitly confirm it's new/excluded and why) before Phase 2 data
  migration is allowed to run.

`output/` is gitignored — these reports contain real names and are not
meant to be committed.

## Setup

Both scripts need a second, separate DB connection to Engagement Tracker's
database (Client Scheduler's own connection comes for free via its existing
`includes/db.php`). Provide it one of two ways:

**Option A — environment variables:**
```bash
export ET_DB_HOST=...
export ET_DB_USER=...
export ET_DB_PASSWORD=...
export ET_DB_NAME=...
```

**Option B — a local file** (gitignored, never committed):
Create `tools/audit-migration/.env.et` next to these scripts with
Engagement Tracker's own `.env` values, same key names:
```
DB_HOST=...
DB_USER=...
DB_PASSWORD=...
DB_NAME=...
```

If neither is set, the scripts print exactly what's missing and exit
without connecting to anything.

## Running

From the Client Scheduler project root:

```bash
php tools/audit-migration/1-identity-crosswalk.php
php tools/audit-migration/2-engagement-crosswalk.php
```

Each prints a short summary (total checked / exact matches / needs review)
and the paths of the two CSVs it wrote.

## After running

1. Open both `*_UNMATCHED_*.csv` files.
2. For every row: either it's a genuine near-miss (fix a nickname/typo and
   re-run), or it's genuinely new (a person or engagement that doesn't
   exist yet on the Client Scheduler side and needs to be created there
   before Phase 2), or it's something that should be excluded — note which,
   and why, directly in the CSV or a side doc.
3. Once every row on both unmatched lists is accounted for, Phase 2 (data
   migration) can run using the `exact`-matched IDs from the full reports.
