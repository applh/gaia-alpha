# GraphsManagement Plugin

The GraphsManagement plugin provides comprehensive data visualization capabilities for the Gaia Alpha CMS, enabling users to create, manage, and embed custom charts and graphs.

## Objective

Create a powerful graphs management system that allows users to:
- Define custom data sources (manual data entry, database queries, API endpoints)
- Create and configure multiple chart types (line, bar, pie, area, scatter, doughnut, radar, polar area)
- Embed graphs in pages via shortcodes
- Manage graph collections and dashboards
- Access graph functionality via MCP tools for AI-assisted data visualization

## Architecture

### Database Schema

The plugin adds two tables to track graphs and collections:

1. **`cms_graphs`**: Stores graph definitions
   - `id`, `user_id`, `title`, `description`
   - `chart_type`: Type of chart (line, bar, pie, etc.)
   - `data_source_type`: Type of data source (manual, database, api)
   - `data_source_config`: JSON configuration for data source
   - `chart_config`: JSON configuration for Chart.js options
   - `refresh_interval`: Auto-refresh interval in seconds
   - `is_public`: Whether graph can be embedded publicly
   - `created_at`, `updated_at`

2. **`cms_graph_collections`**: Stores graph collections/dashboards
   - `id`, `user_id`, `name`, `description`
   - `graph_ids`: JSON array of graph IDs
   - `layout_config`: JSON configuration for dashboard layout
   - `created_at`, `updated_at`

### Backend Components

- **[GraphService](file:///Users/lh/Downloads/antig/gaia-alpha/plugins/GraphsManagement/class/Service/GraphService.php)**: Business logic layer handling CRUD operations, data fetching, and validation
- **[GraphController](file:///Users/lh/Downloads/antig/gaia-alpha/plugins/GraphsManagement/class/Controller/GraphController.php)**: REST API controller exposing 11 endpoints for graph and collection management

### Frontend Components

- **[GraphsManagement.js](file:///Users/lh/Downloads/antig/gaia-alpha/plugins/GraphsManagement/resources/js/GraphsManagement.js)**: Vue 3 component with graph list, editor, and collections views
- **[ChartPreview.js](file:///Users/lh/Downloads/antig/gaia-alpha/plugins/GraphsManagement/resources/js/components/ChartPreview.js)**: Reusable chart preview component

## Configuration

The plugin uses **Chart.js v4.x** (local ES Module build) stored in `resources/js/vendor/chart.js`. The library is automatically registered in the import map for easy importing.

## Key Features

### Chart Types

Supports 8 chart types:
- **Line Chart**: Trend visualization
- **Bar Chart**: Comparison visualization
- **Pie Chart**: Proportion visualization
- **Doughnut Chart**: Proportion with center space
- **Area Chart**: Filled line chart
- **Scatter Plot**: Correlation visualization
- **Radar Chart**: Multi-variable comparison
- **Polar Area Chart**: Radial data visualization

### Data Source Types

#### 1. Manual Data Entry
Enter data directly in Chart.js JSON format:
```json
{
  "labels": ["January", "February", "March"],
  "datasets": [{
    "label": "Sales",
    "data": [12, 19, 3]
  }]
}
```

#### 2. Database Query
Execute SELECT queries against the CMS database:
- **Query**: SQL SELECT statement (validated for security)
- **Label Column**: Column to use for chart labels
- **Value Column**: Column to use for chart values
- **Dataset Label**: Label for the dataset

Example:
```sql
SELECT page_path, COUNT(*) as visits 
FROM cms_analytics_visits 
GROUP BY page_path 
ORDER BY visits DESC 
LIMIT 10
```

#### 3. External API
Fetch data from external APIs:
- **URL**: API endpoint URL
- **Data Path**: Dot notation path to data in response (e.g., `data.results`)
- **Headers**: Optional authentication headers

### Graph Management

- **Create/Edit**: Full-featured graph editor with live preview
- **Search & Filter**: Search by title/description, filter by chart type
- **Delete**: Remove graphs (automatically removes from collections)
- **Public Embedding**: Toggle public access for embedding

### Collections

Group related graphs into dashboards:
- Create named collections
- Add multiple graphs to a collection
- Embed entire collections via shortcode

## MCP Tools

The plugin provides 4 MCP tools for AI-assisted graph management:

### `list_graphs`
List all graphs with optional filtering.

**Parameters:**
- `site` (optional): Site domain
- `chart_type` (optional): Filter by chart type
- `search` (optional): Search query
- `limit` (optional): Maximum results

**Example:**
```bash
php cli.php mcp:call list_graphs '{"chart_type":"line","limit":10}'
```

### `create_graph`
Create a new graph programmatically.

**Parameters:**
- `title` (required): Graph title
- `chart_type` (required): Chart type
- `data_source_type` (required): Data source type
- `data_source_config` (required): Data source configuration
- `chart_config` (optional): Chart.js options
- `is_public` (optional): Public access flag
- `site` (optional): Site domain

**Example:**
```bash
php cli.php mcp:call create_graph '{
  "title": "Page Views",
  "chart_type": "bar",
  "data_source_type": "manual",
  "data_source_config": {
    "labels": ["Home", "About", "Contact"],
    "datasets": [{"label": "Views", "data": [100, 50, 30]}]
  }
}'
```

### `get_graph_data`
Fetch graph metadata and current data.

**Parameters:**
- `graph_id` (required): Graph ID
- `site` (optional): Site domain

**Example:**
```bash
php cli.php mcp:call get_graph_data '{"graph_id":1}'
```

### `update_graph`
Update an existing graph configuration.

**Parameters:**
- `graph_id` (required): Graph ID
- `title` (optional): New title
- `description` (optional): New description
- `chart_type` (optional): New chart type
- `data_source_config` (optional): Updated data source config
- `chart_config` (optional): Updated chart config
- `is_public` (optional): Updated public status
- `site` (optional): Site domain

**Example:**
```bash
php cli.php mcp:call update_graph '{"graph_id":1,"title":"Updated Title"}'
```

## REST API Endpoints

### Graph Management
- `GET /@/graphs` - List all graphs for current user
- `GET /@/graphs/:id` - Get graph details
- `POST /@/graphs` - Create new graph
- `PUT /@/graphs/:id` - Update graph
- `DELETE /@/graphs/:id` - Delete graph
- `GET /@/graphs/:id/data` - Fetch graph data

### Collection Management
- `GET /@/graphs/collections` - List collections
- `GET /@/graphs/collections/:id` - Get collection with graphs
- `POST /@/graphs/collections` - Create collection
- `PUT /@/graphs/collections/:id` - Update collection
- `DELETE /@/graphs/collections/:id` - Delete collection

### Public Access
- `GET /@/graphs/:id/embed` - Get public graph data (no auth required if `is_public=1`)

## Admin UI

Access the Graphs Management interface from the admin panel under **Content > Graphs**.

### Graph List View
- Grid layout with chart previews
- Search by title/description
- Filter by chart type
- Quick delete action
- Click to edit

### Graph Editor
- **Basic Info**: Title, description, chart type selector
- **Data Source Configuration**: Tabbed interface for manual/database/API
- **Live Preview**: Real-time chart preview
- **Test Data Source**: Validate configuration before saving
- **Embed Code**: Generate shortcode for embedding

### Collections View
- List all collections
- Create new collections
- Delete collections

## Shortcode Usage

### Embed Single Graph
```
[graph id="123"]
[graph id="123" width="800" height="400"]
```

### Embed Collection
```
[collection id="5"]
```

The shortcode automatically:
1. Fetches graph/collection data
2. Checks public access permissions
3. Injects Chart.js library
4. Renders canvas and initializes chart

## Hooks

The plugin uses the following hooks:

- `framework_init`: Initialize database tables
- `framework_load_controllers_after`: Register GraphController
- `auth_session_data`: Inject Graphs menu item
- `html_head`: Add Chart.js to import map
- `content_render`: Process graph shortcodes

## Security

- **Authentication Required**: All management endpoints require user authentication
- **User Isolation**: Users can only access their own graphs
- **SQL Injection Protection**: Database queries restricted to SELECT only, validated and sanitized
- **Public Access Control**: Graphs must be explicitly marked as public for embedding
- **XSS Protection**: All user input is sanitized

## Performance

- **Database Indexes**: Indexes on `user_id`, `chart_type`, and `created_at`
- **Lazy Loading**: Graph data loaded on demand
- **Efficient Queries**: Optimized SQL with proper indexing
- **Local Chart.js**: No external CDN dependencies

## Future Enhancements

Potential improvements for future versions:

1. **Advanced Data Sources**:
   - CSV/Excel file imports
   - Real-time data streaming (WebSockets)
   - Webhook integrations
   - Integration with Analytics plugin

2. **Enhanced Visualizations**:
   - Heatmaps and treemaps
   - Geographic maps with data overlays
   - 3D visualizations
   - Custom color schemes and themes

3. **Collaboration Features**:
   - Share graphs with specific users
   - Commenting on graphs
   - Version history and rollback

4. **Advanced Analytics**:
   - Trend analysis and forecasting
   - Anomaly detection
   - Statistical calculations

5. **Export & Integration**:
   - Export to PDF/PNG/SVG
   - Email scheduled reports
   - Integration with Component Builder
   - Slack/Discord notifications

6. **AI-Powered Features**:
   - Auto-suggest chart types based on data
   - Natural language query builder
   - Automated insights generation
