# API Documentation

## Media API
The Media API handles secure image serving, resizing, and caching.

**Endpoint**: `/media/{user_id}/{filename}`

### Parameters
| Query Param | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `w` | Int | `0` (orig) | Target width |
| `h` | Int | `0` (orig) | Target height |
| `q` | Int | `80` | Quality (1-100) |
| `fit` | String | `contain` | `contain` or `cover` |

### Examples
- **Original**: `/media/1/image.jpg`
- **Thumbnail (200px wide)**: `/media/1/image.jpg?w=200`
- **Square Crop (150x150)**: `/media/1/image.jpg?w=150&h=150&fit=cover`

---

## Core JSON API
These endpoints return JSON and require Session Authentication.

### Global
- `GET /api/me`: Returns current user info (`id`, `username`, `level`).

### Todo Management
- `GET /api/todos`: List user's todos.
- `POST /api/todos`: Create a new todo.
  - Body: `{"title": "My Task"}`
- `POST /api/todos/toggle`: Toggle completion status.
  - Body: `{"id": 123}`
- `POST /api/todos/delete`: Delete a todo.
  - Body: `{"id": 123}`

### Admin Handlers
- `POST /api/admin/users/delete`: Delete a user (Level 100+ only).
  - Body: `{"id": 456}`

### File Uploads
- `POST /api/upload`: Upload an image.
  - **Multipart Form Data**: field `file`
  - **Returns**: `{"url": "/media/1/newimage.jpg"}`

### CMS
- `GET /api/cms/pages`: List all pages.
  - **Query Params**:
    - `cat`: "page" | "image" (default: "page")
- `POST /api/cms/pages`: Create a page.
  - **Body**: `{"title":"...","slug":"...","content":"...","image":"...","cat":"page"}`
- `PATCH /api/cms/pages/{id}`: Update a page.
  - **Body**: `{"title":"...","content":"...","image":"..."}`
- `DELETE /api/cms/pages/{id}`: Delete a page.
- `POST /api/cms/upload`: Upload an image.
  - **Multipart Form Data**: field `image`
  - **Returns**: `{"url": "/media/1/newimage.webp"}`
  - **Processing**: 
    - Accepts JPEG, PNG, WebP
    - Converts to WebP format
    - Resizes to max 3840x2160 if larger
    - Stores in `uploads/{user_id}/`
    - Auto-creates CMS entry with `cat='image'`

### Admin
- `GET /api/admin/stats`: Get system statistics (Admin only).
  - **Returns**: `{"users": 12, "todos": 6, "pages": 3, "images": 1}`
- `GET /api/admin/users`: List all users (Admin only).
- `POST /api/admin/users`: Create a user (Admin only).
  - **Body**: `{"username":"...","password":"...","level":10}`
- `PATCH /api/admin/users/{id}`: Update a user (Admin only).
  - **Body**: `{"level":100,"password":"..."}`
- `DELETE /api/admin/users/{id}`: Delete a user (Admin only).

### Database Manager (Admin Only)
- `GET /api/admin/db/tables`: List all database tables.
  - **Returns**: `{"tables": ["users", "todos", "cms_pages", "data_store"]}`
- `GET /api/admin/db/table/{tableName}`: Get table schema and data.
  - **Returns**: `{"table": "users", "schema": [...], "data": [...], "count": 10}`
- `POST /api/admin/db/query`: Execute a SQL query.
  - **Body**: `{"query": "SELECT * FROM users WHERE level >= 100"}`
  - **Returns**: 
    - SELECT: `{"success": true, "type": "select", "results": [...], "count": 5}`
    - Other: `{"success": true, "type": "modification", "affected_rows": 3}`
- `POST /api/admin/db/table/{tableName}`: Create a record.
  - **Body**: `{"column1": "value1", "column2": "value2"}`
  - **Returns**: `{"success": true, "id": "123"}`
- `PATCH /api/admin/db/table/{tableName}/{id}`: Update a record.
  - **Body**: `{"column1": "new_value"}`
  - **Returns**: `{"success": true}`
- `DELETE /api/admin/db/table/{tableName}/{id}`: Delete a record.
  - **Returns**: `{"success": true}`
