-- Garrett pointed out that not every PCI engagement produces a ROC (Report
-- on Compliance) - smaller merchants/service providers instead get an AOC
-- (Attestation of Compliance) or file a SAQ (Self-Assessment Questionnaire,
-- of which there are several variants). Yesterday's roc_delivery_date
-- column was too narrow - renaming it to the PCI-generic
-- pci_delivery_date, and adding a separate pci_assessment_type column
-- (ROC / AOC / SAQ variant) so the two are tracked independently, same
-- pattern as soc_type alongside the SOC as-of/review-period dates.

ALTER TABLE audit_engagement_details
  CHANGE COLUMN roc_delivery_date pci_delivery_date DATE NULL,
  ADD COLUMN pci_assessment_type VARCHAR(40) NULL AFTER pci_delivery_date;
