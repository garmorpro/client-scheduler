-- Backfill audit_engagement_details / audit_engagement_timeline rows for
-- engagements that never got one.
--
-- Root cause: add_engagement.php only ever inserted an audit_engagement_
-- details row when TSC was set (never audit_engagement_timeline at all),
-- and unarchive_engagement.php didn't insert either row when restoring an
-- archived engagement back into a fresh engagements row. Without a
-- timeline row specifically, the View Engagement panel's "Timeline & Key
-- Dates" card doesn't render at all for that engagement (renderTimelineSection()
-- returns '' when audit.timeline is missing) - not just empty, the whole
-- card is gone, which is what surfaced this ("some engagements have the
-- timeline and some don't"). Both write paths are fixed as of this same
-- commit; this backfills everything created before the fix.
--
-- Safe to re-run - INSERT IGNORE is a no-op for any engagement that
-- already has a row.

INSERT IGNORE INTO audit_engagement_details (engagement_id)
SELECT e.engagement_id
FROM engagements e
LEFT JOIN audit_engagement_details d ON d.engagement_id = e.engagement_id
WHERE d.engagement_id IS NULL;

INSERT IGNORE INTO audit_engagement_timeline (engagement_id)
SELECT e.engagement_id
FROM engagements e
LEFT JOIN audit_engagement_timeline t ON t.engagement_id = e.engagement_id
WHERE t.engagement_id IS NULL;
