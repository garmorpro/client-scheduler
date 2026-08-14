-- A PCI engagement can require more than one assessment type at once (e.g.
-- both a SAQ D (Merchant) and an AOC) - VARCHAR(40) was sized for a single
-- value from the list; widened to match tsc's VARCHAR(255) since both now
-- store a comma-separated list the same way.

ALTER TABLE audit_engagement_details
  MODIFY COLUMN pci_assessment_type VARCHAR(255) NULL;
