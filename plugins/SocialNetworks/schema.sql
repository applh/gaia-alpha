CREATE TABLE IF NOT EXISTS cms_social_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    platform VARCHAR(50) NOT NULL, -- x, linkedin, youtube, tiktok
    account_name VARCHAR(255),
    account_id VARCHAR(255),
    access_token TEXT,
    refresh_token TEXT,
    expires_at DATETIME,
    settings TEXT, -- JSON settings (API keys, etc.)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cms_social_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    account_id INTEGER,
    content TEXT,
    media_urls TEXT, -- JSON array of media links
    status VARCHAR(50) DEFAULT 'pending', -- pending, published, failed
    platform_post_id VARCHAR(255),
    error_message TEXT,
    published_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES cms_social_accounts(id)
);
