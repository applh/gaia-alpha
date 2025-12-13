CREATE TABLE IF NOT EXISTS forms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    schema TEXT NOT NULL DEFAULT '[]', -- JSON array of fields
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    submit_label TEXT DEFAULT 'Submit',
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS form_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    form_id INTEGER NOT NULL,
    data TEXT NOT NULL, -- JSON object of submitted data
    ip_address TEXT,
    user_agent TEXT,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(form_id) REFERENCES forms(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_forms_slug ON forms(slug);
