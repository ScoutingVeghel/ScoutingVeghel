-- Applied to production on 7 November 2026. Do not re-run blindly.
-- Additive schema migration; only after full verified production backup.
ALTER TABLE leden_public_requests ADD COLUMN changes LONGTEXT NULL;
ALTER TABLE leden_public_requests MODIFY COLUMN request_type ENUM('leave','transfer','change') NOT NULL;
ALTER TABLE leden_mail_outbox MODIFY COLUMN entity_type ENUM('register','leave','transfer','change') NOT NULL;
