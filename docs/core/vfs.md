# Virtual File System (VFS) Pattern

The Virtual File System pattern allows an application to mimic a hierarchical file structure using a relational database (typically SQLite for isolation).

## Use Cases

- **User Assets**: Storing files that shouldn't be accessible directly via web server paths.
- **Prototyping**: Building file-like structures for experiments.
- **Sandbox**: Providing a file system interface to AI agents or users without granting real FS access.

## Implementation

### Database Schema

A minimal VFS requires a table that supports parent-child relationships:

```sql
CREATE TABLE vfs_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parent_id INTEGER DEFAULT 0,
    name TEXT NOT NULL,
    type TEXT NOT NULL, -- 'file' or 'folder'
    content TEXT,
    size INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_parent_id ON vfs_items(parent_id);
```

### Recursive Operations

Listing and deleting in a VFS often requires recursion.

```php
public static function listItems(int $parentId): array
{
    // Fetch all items where parent_id = $parentId
}

public static function deleteItem(int $id): bool
{
    // If folder, recursively delete children
    // Then delete the item itself
}
```

## Integration with File Explorer

The File Explorer plugin provides a UI for this pattern, allowing users to switch between the "Real" and "Virtual" modes seamlessly.
