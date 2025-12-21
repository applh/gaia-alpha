CREATE TABLE IF NOT EXISTS cms_analytics_visits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER DEFAULT 0,
    page_path VARCHAR(255) NOT NULL,
    visitor_ip VARCHAR(45),
    user_agent TEXT,
    referrer TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_analytics_path ON cms_analytics_visits(page_path);
CREATE INDEX IF NOT EXISTS idx_analytics_timestamp ON cms_analytics_visits(timestamp);
