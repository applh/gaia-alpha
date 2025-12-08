-- Add missing columns to cms_pages table
ALTER TABLE cms_pages ADD COLUMN slug TEXT;
ALTER TABLE cms_pages ADD COLUMN content TEXT;
ALTER TABLE cms_pages ADD COLUMN created_at DATETIME;
ALTER TABLE cms_pages ADD COLUMN updated_at DATETIME;
ALTER TABLE cms_pages ADD COLUMN cat TEXT DEFAULT 'page';
ALTER TABLE cms_pages ADD COLUMN tag TEXT;
