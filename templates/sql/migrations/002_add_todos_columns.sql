-- Add timestamp columns to todos table
ALTER TABLE todos ADD COLUMN created_at DATETIME;
ALTER TABLE todos ADD COLUMN updated_at DATETIME;
