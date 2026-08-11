-- Adds audit types: a manageable list (System Settings) that gets selected
-- per engagement, and then per staff assignment on Master Schedule (since
-- different people can be staffed on different audit types within the same
-- engagement).
--
-- Note: "HISTRUST" in the original request has been spelled "HITRUST" here -
-- that's the actual name of the compliance framework (HITRUST CSF). Rename
-- it via the Audit Types manager if a different name was actually intended.

CREATE TABLE audit_types (
  audit_type_id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  color VARCHAR(7) NOT NULL DEFAULT '#4f8ef7',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (audit_type_id),
  UNIQUE KEY name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Which audit types an engagement covers (multi-select at engagement
-- creation/edit time).
CREATE TABLE engagement_audit_types (
  engagement_id INT NOT NULL,
  audit_type_id INT NOT NULL,
  PRIMARY KEY (engagement_id, audit_type_id),
  KEY audit_type_id (audit_type_id),
  CONSTRAINT fk_eat_engagement FOREIGN KEY (engagement_id) REFERENCES engagements (engagement_id) ON DELETE CASCADE,
  CONSTRAINT fk_eat_audit_type FOREIGN KEY (audit_type_id) REFERENCES audit_types (audit_type_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Which specific audit type a given staffing assignment is working on -
-- nullable since an engagement may have zero or one audit type selected, in
-- which case there's nothing to choose.
ALTER TABLE entries
  ADD COLUMN audit_type_id INT NULL AFTER engagement_id,
  ADD CONSTRAINT fk_entries_audit_type FOREIGN KEY (audit_type_id) REFERENCES audit_types (audit_type_id) ON DELETE SET NULL;

INSERT INTO audit_types (name, color) VALUES
('SOC 1', '#4f8ef7'),
('SOC 2', '#9b6bd6'),
('ISO 27001', '#2fb5a0'),
('ISO 42001', '#d67aa8'),
('PCI', '#7a8fd6'),
('HIPAA', '#c9a227'),
('HITRUST', '#5aa8d6'),
('NIST', '#b45f9e'),
('FISMA', '#6a7f9e');
