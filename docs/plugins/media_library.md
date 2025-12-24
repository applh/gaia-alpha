# MediaLibrary Plugin

The MediaLibrary plugin provides comprehensive Digital Asset Management (DAM) features for the Gaia Alpha CMS, enabling better organization, tagging, and search capabilities for media files.

## Objective

Enhance the media management experience by adding:
- **Metadata Tracking**: Store file information, alt text, captions, and dimensions
- **Tagging System**: Organize files with custom tags and color coding
- **Search & Filter**: Find files quickly by name, metadata, or tags
- **Bulk Operations**: Manage multiple files simultaneously
- **AI Integration**: MCP tools for AI-assisted media management

## Architecture

### Database Schema

The plugin adds three tables to track media files and their organization:

1. **`cms_media`**: Stores file metadata including filename, type, size, dimensions, alt text, and caption
2. **`cms_media_tags`**: Stores tag definitions with name, slug, and color
3. **`cms_media_tag_relations`**: Junction table for many-to-many relationships between media and tags

### Backend Components

- **[MediaLibraryService](file:///Users/lh/Downloads/antig/gaia-alpha/plugins/MediaLibrary/class/Service/MediaLibraryService.php)**: Business logic layer handling CRUD operations, search, filtering, and statistics
- **[MediaLibraryController](file:///Users/lh/Downloads/antig/gaia-alpha/plugins/MediaLibrary/class/Controller/MediaLibraryController.php)**: REST API controller exposing 11 endpoints for file and tag management

### Frontend Component

- **[MediaLibrary.js](file:///Users/lh/Downloads/antig/gaia-alpha/plugins/MediaLibrary/resources/js/MediaLibrary.js)**: Vue 3 component with grid/list views, drag-and-drop upload, tag management, search, and bulk operations
- **Style Registration**: Automatically registers `media-library.css` for consistent styling across the admin panel.

## Configuration

No configuration required. The plugin uses the existing media upload directory (`data/uploads/{userId}/`) and automatically creates database tables on first run.

## Key Features

### File Management
- **Upload**: Drag-and-drop or click to upload images
- **Metadata**: Edit alt text, captions, and filenames
- **Preview**: Thumbnail previews with automatic image optimization
- **Delete**: Remove files from both database and filesystem

### Tagging System
- **Create Tags**: Custom tags with color coding
- **Assign Tags**: Add multiple tags to files
- **Filter by Tag**: View all files with a specific tag
- **Tag Statistics**: See file count per tag

### Search & Organization
- **Full-Text Search**: Search across filenames, alt text, and captions
- **Tag Filtering**: Filter files by one or more tags
- **View Modes**: Toggle between grid and list layouts
- **Statistics**: View total files, storage usage, and file type breakdown

### Bulk Operations
- **Multi-Select**: Select multiple files with checkboxes
- **Bulk Tagging**: Assign tags to multiple files at once
- **Bulk Delete**: Remove multiple files simultaneously

## MCP Tools

The plugin provides four MCP tools for AI-assisted media management:

### `list_media_files`
List all media files with optional filtering by tags or search query.

**Parameters:**
- `site` (optional): Site domain
- `tag` (optional): Filter by tag slug
- `search` (optional): Search query
- `limit` (optional): Maximum number of results

**Example:**
```bash
php cli.php mcp:call list_media_files '{"search":"logo"}'
```

### `tag_media`
Assign or remove tags from media files. Can create new tags automatically.

**Parameters:**
- `media_id` (required): ID of the media file
- `tag_ids` (optional): Array of tag IDs to assign
- `tag_names` (optional): Array of tag names (creates if not exists)

**Example:**
```bash
php cli.php mcp:call tag_media '{"media_id":1,"tag_names":["product","featured"]}'
```

### `search_media`
Search media files by filename, alt text, or caption.

**Parameters:**
- `query` (required): Search query
- `site` (optional): Site domain

**Example:**
```bash
php cli.php mcp:call search_media '{"query":"banner"}'
```

### `get_media_stats`
Retrieve statistics about the media library.

**Parameters:**
- `site` (optional): Site domain

**Example:**
```bash
php cli.php mcp:call get_media_stats '{}'
```

## REST API Endpoints

### Files
- `GET /@/media-library/files` - List all files
- `GET /@/media-library/files/:id` - Get file details
- `POST /@/media-library/files` - Upload new file
- `PUT /@/media-library/files/:id` - Update file metadata
- `DELETE /@/media-library/files/:id` - Delete file

### Tags
- `GET /@/media-library/tags` - List all tags
- `POST /@/media-library/tags` - Create new tag
- `DELETE /@/media-library/tags/:id` - Delete tag

### Operations
- `POST /@/media-library/files/:id/tags` - Assign tags to file
- `GET /@/media-library/search?q=query` - Search files
- `GET /@/media-library/stats` - Get library statistics

## Admin UI

Access the Media Library from the admin panel under **Content > Media Library**.

### Grid View
- Visual thumbnail grid layout
- Hover to see file details
- Click to edit file metadata
- Checkbox selection for bulk operations

### List View
- Tabular layout with all file information
- Sortable columns
- Inline actions (edit, delete)
- Bulk selection checkboxes

### File Upload
- Drag-and-drop support
- Multiple file upload
- Automatic metadata extraction (dimensions, file size)
- Progress indication

### Tag Management
- Create tags with custom colors
- Filter files by tag
- Tag statistics (file count per tag)
- Assign/remove tags from files

## Hooks

The plugin uses the following hooks:

- `framework_load_controllers_after`: Registers the MediaLibraryController
- `auth_session_data`: Injects the Media Library menu item into the admin panel

## Security

- **Authentication Required**: All endpoints require user authentication
- **User Isolation**: Users can only access their own media files
- **File Type Validation**: Only allows image uploads (JPEG, PNG, WebP, AVIF)
- **SQL Injection Protection**: Uses parameterized queries throughout
- **XSS Protection**: All user input is sanitized before display

## Performance

- **Indexed Queries**: Database indexes on user_id, filename, and created_at
- **Lazy Loading**: Files loaded on demand
- **Image Optimization**: Automatic thumbnail generation via existing Media class
- **Efficient Filtering**: Tag filtering uses JOIN queries for optimal performance

## Future Enhancements

Potential improvements for future versions:

- **Folders/Collections**: Organize files into hierarchical folders
- **Advanced Search**: Filter by date range, file type, dimensions
- **Image Editing**: Basic crop, resize, and filter operations
- **CDN Integration**: Automatic upload to external CDN
- **Duplicate Detection**: Identify and merge duplicate files
- **Usage Tracking**: See where media files are used across the site
