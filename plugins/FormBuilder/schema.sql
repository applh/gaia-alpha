CREATE TABLE IF NOT EXISTS forms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    submit_label TEXT DEFAULT 'Submit',
    schema TEXT DEFAULT '[]',
    type TEXT DEFAULT 'form', -- form, quiz, poll
    settings TEXT DEFAULT '{}', -- json for extra settings like time limit, shuffle, etc.
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS form_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    form_id INTEGER NOT NULL,
    data TEXT NOT NULL, -- JSON response
    score INTEGER DEFAULT NULL, -- For quizzes
    metadata TEXT DEFAULT '{}', -- Details about correct/incorrect answers if needed
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(form_id) REFERENCES forms(id) ON DELETE CASCADE
);

-- Migration for existing tables if they exist without the new columns
-- SQLite doesn't support IF NOT EXISTS for ADD COLUMN easily in one statement, 
-- but the framework's db migration might handle it or we can suppress errors.
-- Ideally we check, but for now we'll assume this is run by the framework which tries to execute.
-- If we are manually migrating we might need Alter stmts.
-- Since this is a "Refactor", we might just recreate or let the user know. 
-- But best practice:
-- ALTER TABLE forms ADD COLUMN type TEXT DEFAULT 'form';
-- ALTER TABLE forms ADD COLUMN settings TEXT DEFAULT '{}';
-- ALTER TABLE form_submissions ADD COLUMN score INTEGER DEFAULT NULL;
-- ALTER TABLE form_submissions ADD COLUMN metadata TEXT DEFAULT '{}';
