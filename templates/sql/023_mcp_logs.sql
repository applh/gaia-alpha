CREATE TABLE IF NOT EXISTS cms_mcp_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(255),
    method VARCHAR(100),
    params TEXT,
    result_status VARCHAR(20), -- 'success', 'error'
    error_message TEXT,
    duration FLOAT, -- Execution time in seconds
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    site_domain VARCHAR(255) DEFAULT 'default',
    client_name VARCHAR(255),
    client_version VARCHAR(50)
);

CREATE INDEX IF NOT EXISTS idx_mcp_session ON cms_mcp_logs(session_id);
CREATE INDEX IF NOT EXISTS idx_mcp_method ON cms_mcp_logs(method);
CREATE INDEX IF NOT EXISTS idx_mcp_timestamp ON cms_mcp_logs(timestamp);
