-- Add SEO and Schema.org columns to cms_pages
ALTER TABLE cms_pages ADD COLUMN canonical_url TEXT;
ALTER TABLE cms_pages ADD COLUMN schema_type TEXT;
ALTER TABLE cms_pages ADD COLUMN schema_data TEXT;
