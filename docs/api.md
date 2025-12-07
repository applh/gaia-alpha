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
- `DELETE /api/cms/pages/{id}`: Delete a page.
