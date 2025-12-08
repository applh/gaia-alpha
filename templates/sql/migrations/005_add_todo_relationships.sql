-- Add parent_id and labels columns to todos table
ALTER TABLE todos ADD COLUMN parent_id INTEGER;
ALTER TABLE todos ADD COLUMN labels TEXT;
