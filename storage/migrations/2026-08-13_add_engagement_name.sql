-- Adds an optional engagement_name, separate from the client. Today an
-- engagement's only identity is client_id + year - fine for a client with
-- one engagement a year, but indistinguishable when a client runs several
-- concurrent engagements under different products (e.g. LivePerson running
-- Conversation Cloud, Tenfold, and Voicebase all in the same year - all
-- three would just show up as "LivePerson" everywhere, and the bulk
-- import's client_name+year uniqueness key would treat the 2nd and 3rd as
-- duplicates of the 1st and silently skip them).
--
-- Optional by design (per Garrett): when blank, the client's own name
-- doubles as the engagement's name, exactly as it always implicitly did
-- before this column existed - see includes/engagement_helpers.php.

ALTER TABLE engagements
  ADD COLUMN engagement_name VARCHAR(150) NULL AFTER client_name;
