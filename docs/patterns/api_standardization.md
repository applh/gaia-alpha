# API Standardization Pattern

Gaia Alpha standardizes all frontend API interactions through a centralized helper to ensure consistency, security, and ease of maintenance.

## The `Api.js` Helper

Located at `resources/js/utils/Api.js`, this helper provides a standard interface for all outgoing requests.

### Key Features
- **Automatic Prefixing**: Prepends `/@/api/` to all relative paths.
- **Content-Type Handling**: Automatically manages `application/json` headers.
- **FormData Support**: Native support for `FormData` (multipart) without manual header configuration.
- **Global Error Handling**: Integrated 401 Unauthorized detection and redirection to `#/login`.

## Import Map

The helper is mapped to `api` in the system importmap, making it easily accessible:

```javascript
import { api } from 'api';
```

## Usage Examples

### GET Request
```javascript
const todos = await api.get('todos');
```

### POST Request (JSON)
```javascript
await api.post('todos', { title: 'New Task' });
```

### POST Request (FormData)
```javascript
const formData = new FormData();
formData.append('file', file);
await api.post('media-library/files', formData);
```

### Advanced Options
The `options` object is passed through to native `fetch`:
```javascript
await api.get('audit-logs', { 
    headers: { 'X-Custom-Header': 'Value' } 
});
```

## Best Practices
1. **Never use `fetch()` directly** for internal API calls.
2. **Handle errors** locally using `try...catch` blocks for UI-specific feedback.
3. **Relative paths only**: Always use paths relative to the API base (e.g., `todos` instead of `/@/api/todos`).
