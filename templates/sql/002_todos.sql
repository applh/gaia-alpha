CREATE TABLE IF NOT EXISTS todos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    completed INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    labels TEXT,
    parent_id INTEGER DEFAULT NULL,
    position REAL DEFAULT 0,
    start_date DATETIME,
    end_date DATETIME,
    color VARCHAR(7),
    FOREIGN KEY(user_id) REFERENCES users(id)
);
