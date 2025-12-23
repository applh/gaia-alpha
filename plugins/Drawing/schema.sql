CREATE TABLE IF NOT EXISTS cms_drawings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    content TEXT, -- JSON content of the drawing (paths, layers, etc.)
    level VARCHAR(20) DEFAULT 'beginner',
    background_image VARCHAR(255), -- URL or path to background image
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
