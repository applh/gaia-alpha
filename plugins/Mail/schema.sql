-- Mail Plugin Schema

-- Newsletters
CREATE TABLE IF NOT EXISTS newsletters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    subject VARCHAR(255) NOT NULL,
    content_md TEXT,
    content_html TEXT,
    status VARCHAR(50) DEFAULT 'draft', -- draft, sent, scheduled
    sent_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Mailing Lists
CREATE TABLE IF NOT EXISTS newsletter_lists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Subscribers
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255),
    status VARCHAR(50) DEFAULT 'active', -- active, unsubscribed
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Joint table for List <-> Subscriber could be added later if needed, 
-- but controller logic for now is simple.
