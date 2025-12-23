-- API usage logging schema
CREATE TABLE IF NOT EXISTS cms_api_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    method VARCHAR(10) NOT NULL,
    path TEXT NOT NULL,
    route_pattern TEXT,
    status_code INTEGER,
    duration FLOAT,
    user_id INTEGER NULL,
    site_domain VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_api_logs_timestamp ON cms_api_logs(timestamp);
