-- Migration: Create Media Library Tables
-- Description: Add tables for media file metadata, tags, and tag relationships

-- Media files metadata table
CREATE TABLE IF NOT EXISTS cms_media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INTEGER NOT NULL,
    width INTEGER,
    height INTEGER,
    alt_text TEXT,
    caption TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Media tags table
CREATE TABLE IF NOT EXISTS cms_media_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#6366f1',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Media-Tag relationships (many-to-many)
CREATE TABLE IF NOT EXISTS cms_media_tag_relations (
    media_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (media_id, tag_id),
    FOREIGN KEY(media_id) REFERENCES cms_media(id) ON DELETE CASCADE,
    FOREIGN KEY(tag_id) REFERENCES cms_media_tags(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_media_user ON cms_media(user_id);
CREATE INDEX IF NOT EXISTS idx_media_filename ON cms_media(filename);
CREATE INDEX IF NOT EXISTS idx_media_created ON cms_media(created_at);
CREATE INDEX IF NOT EXISTS idx_tag_slug ON cms_media_tags(slug);
