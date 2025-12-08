-- Add missing columns to users table
ALTER TABLE users ADD COLUMN level INTEGER DEFAULT 10;
ALTER TABLE users ADD COLUMN created_at DATETIME;
ALTER TABLE users ADD COLUMN updated_at DATETIME;
