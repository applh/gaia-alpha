CREATE TABLE IF NOT EXISTS cms_slide_decks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    author_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cms_slide_pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    deck_id INTEGER NOT NULL,
    order_index INTEGER DEFAULT 0,
    content TEXT, -- JSON content of the slide
    background_color VARCHAR(50) DEFAULT '#ffffff',
    slide_type VARCHAR(50) DEFAULT 'drawing', -- 'drawing', 'markdown', etc.
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (deck_id) REFERENCES cms_slide_decks(id) ON DELETE CASCADE
);
