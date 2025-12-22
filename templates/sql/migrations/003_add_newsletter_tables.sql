-- Migration: Add Newsletter Tables
-- dialect: sqlite

CREATE TABLE newsletter_lists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE newsletter_subscribers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255),
    status VARCHAR(50) DEFAULT 'active', -- active, unsubscribed
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE newsletter_subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    subscriber_id INTEGER,
    list_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(subscriber_id) REFERENCES newsletter_subscribers(id) ON DELETE CASCADE,
    FOREIGN KEY(list_id) REFERENCES newsletter_lists(id) ON DELETE CASCADE
);

CREATE TABLE newsletters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    subject VARCHAR(255) NOT NULL,
    content_md TEXT,
    content_html TEXT,
    status VARCHAR(50) DEFAULT 'draft', -- draft, sending, sent
    sent_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
