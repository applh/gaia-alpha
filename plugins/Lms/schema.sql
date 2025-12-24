-- Courses Table
CREATE TABLE IF NOT EXISTS lms_courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    thumbnail VARCHAR(255),
    instructor_id INTEGER,
    status VARCHAR(20) DEFAULT 'draft', -- draft, published, archived
    price DECIMAL(10, 2) DEFAULT 0.00,  -- Basic price (can be overridden by Ecommerce)
    is_free BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Modules (Sections)
CREATE TABLE IF NOT EXISTS lms_modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    sort_order INTEGER DEFAULT 0,
    FOREIGN KEY(course_id) REFERENCES lms_courses(id) ON DELETE CASCADE
);

-- Lessons (Units)
CREATE TABLE IF NOT EXISTS lms_lessons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    module_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    type VARCHAR(20) DEFAULT 'text', -- text, video, quiz
    content TEXT,                    -- HTML content or Video URL
    video_id VARCHAR(255),           -- Integration with MediaLibrary or external provider
    duration INTEGER,                -- in seconds
    is_preview BOOLEAN DEFAULT 0,    -- Free preview available?
    sort_order INTEGER DEFAULT 0,
    FOREIGN KEY(module_id) REFERENCES lms_modules(id) ON DELETE CASCADE
);

-- Enrollments
CREATE TABLE IF NOT EXISTS lms_enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    status VARCHAR(20) DEFAULT 'active', -- active, completed, expired
    enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    FOREIGN KEY(course_id) REFERENCES lms_courses(id) ON DELETE CASCADE
);

-- Progress
CREATE TABLE IF NOT EXISTS lms_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    lesson_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,      -- Denormalized for easier query
    completed BOOLEAN DEFAULT 0,
    completed_at DATETIME NULL,
    last_viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(lesson_id) REFERENCES lms_lessons(id) ON DELETE CASCADE
);
