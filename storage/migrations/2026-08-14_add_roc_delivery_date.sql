-- ROC (Report on Compliance) Delivery Date - relevant to PCI engagements
-- only, same pattern as soc_type's As-of Date/Review Period fields (which
-- only matter for SOC 1/SOC 2). Shows in Add/Edit Engagement whenever PCI
-- is one of the selected audit types, regardless of what else is checked.

ALTER TABLE audit_engagement_details
  ADD COLUMN roc_delivery_date DATE NULL AFTER review_period_end;
