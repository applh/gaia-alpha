-- Backfill timestamps and defaults for existing records
UPDATE users SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL;
UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE updated_at IS NULL;
UPDATE todos SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL;
UPDATE todos SET updated_at = CURRENT_TIMESTAMP WHERE updated_at IS NULL;
UPDATE cms_pages SET cat = 'page' WHERE cat IS NULL;
