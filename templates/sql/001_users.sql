CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    level INTEGER DEFAULT 10,
    created_at DATETIME,
    updated_at DATETIME
);
