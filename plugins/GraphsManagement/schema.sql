-- Graphs Management Plugin Database Schema

-- Table for storing graph definitions
CREATE TABLE IF NOT EXISTS cms_graphs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    chart_type TEXT NOT NULL, -- line, bar, pie, area, scatter, doughnut
    data_source_type TEXT NOT NULL, -- manual, database, api
    data_source_config TEXT NOT NULL, -- JSON configuration
    chart_config TEXT, -- JSON configuration for Chart.js options
    refresh_interval INTEGER DEFAULT 0, -- Auto-refresh interval in seconds (0 = disabled)
    is_public INTEGER DEFAULT 0, -- Whether graph can be embedded publicly
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_graphs_user_id ON cms_graphs(user_id);
CREATE INDEX IF NOT EXISTS idx_graphs_chart_type ON cms_graphs(chart_type);
CREATE INDEX IF NOT EXISTS idx_graphs_created_at ON cms_graphs(created_at);

-- Table for storing graph collections/dashboards
CREATE TABLE IF NOT EXISTS cms_graph_collections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    graph_ids TEXT NOT NULL, -- JSON array of graph IDs
    layout_config TEXT, -- JSON configuration for dashboard layout
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_collections_user_id ON cms_graph_collections(user_id);
CREATE INDEX IF NOT EXISTS idx_collections_created_at ON cms_graph_collections(created_at);
