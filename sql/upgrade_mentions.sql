-- Upgrade script: add mentions column to llx_colabor
-- Run once on existing installations
ALTER TABLE llx_colabor ADD COLUMN mentions LONGTEXT COMMENT 'JSON con menciones guardadas (users, contacts, documents)';
