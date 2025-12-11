CREATE TABLE IF NOT EXISTS cms_pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    slug TEXT NOT NULL,
    content TEXT,
    image TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    cat TEXT DEFAULT 'page',
    tag TEXT,
    FOREIGN KEY(user_id) REFERENCES users(id),
    UNIQUE(slug)
);
