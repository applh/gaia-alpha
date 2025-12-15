-- Admin Components Table
CREATE TABLE admin_components (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    description TEXT,
    category TEXT DEFAULT 'custom',
    icon TEXT DEFAULT 'puzzle',
    view_name TEXT NOT NULL UNIQUE,
    definition TEXT NOT NULL, -- JSON component definition
    generated_code TEXT, -- Generated Vue component code
    version INTEGER DEFAULT 1,
    enabled BOOLEAN DEFAULT 1,
    admin_only BOOLEAN DEFAULT 1,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Component Versions (for rollback)
CREATE TABLE admin_component_versions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    component_id INTEGER NOT NULL,
    version INTEGER NOT NULL,
    definition TEXT NOT NULL,
    generated_code TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (component_id) REFERENCES admin_components(id) ON DELETE CASCADE
);

-- Component Permissions
CREATE TABLE admin_component_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    component_id INTEGER NOT NULL,
    role TEXT NOT NULL,
    can_view BOOLEAN DEFAULT 1,
    can_edit BOOLEAN DEFAULT 0,
    can_delete BOOLEAN DEFAULT 0,
    FOREIGN KEY (component_id) REFERENCES admin_components(id) ON DELETE CASCADE
);
