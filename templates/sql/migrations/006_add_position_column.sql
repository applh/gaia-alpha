ALTER TABLE todos ADD COLUMN position REAL DEFAULT 0;
-- Backfill positions nicely?
-- Maybe separate update if complex logic needed. 
-- For now default 0. Backend will handle sorting.
