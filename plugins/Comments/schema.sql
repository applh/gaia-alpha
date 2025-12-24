-- Comments Table
CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,              -- NULL accounts for guest comments
    
    -- Polymorphic Relation
    commentable_type VARCHAR(100) NOT NULL, -- e.g., 'lms_lesson', 'product', 'blog_post'
    commentable_id INTEGER NOT NULL,
    
    parent_id INTEGER NULL,            -- For nested replies
    
    content TEXT NOT NULL,
    rating INTEGER NULL,               -- 1-5 for reviews, NULL for standard comments
    
    status VARCHAR(20) DEFAULT 'approved', -- approved, pending, spam, trash
    
    author_name VARCHAR(100) NULL,     -- Cached name for display or for guests
    author_email VARCHAR(100) NULL,    -- For guests
    
    meta_data TEXT NULL,               -- JSON for extra fields (e.g., verified_purchase)
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY(parent_id) REFERENCES comments(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_comments_lookup ON comments(commentable_type, commentable_id, status);
CREATE INDEX IF NOT EXISTS idx_comments_user ON comments(user_id);
