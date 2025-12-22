# File Explorer Plugin

The File Explorer plugin provides a comprehensive interface for managing files on the server and within virtual file systems.

## Features

- **Real File System**: Browse, read, write, rename, and delete files on the server.
- **Virtual File System (VFS)**: Create and manage isolated file systems stored in SQLite databases.
- **Tree View**: Intuitive navigation with a recursive directory tree.
- **Drag & Drop**: Move files and folders by dragging them between directories.
- **Text Editor**: Edit text-based files directly in the browser.
- **Image Manipulator**: Rotate, flip, filter, and crop images using a canvas-based editor.

## Architecture

The plugin follows the standard Gaia Alpha plugin pattern:

- **Service Layer**: 
    - `FileExplorerService`: Handles standard PHP file operations.
    - `VirtualFsService`: Manages SQLite databases and a hierarchical `vfs_items` table.
- **Controller**: `FileExplorerController` provides API endpoints for all operations, bridging the UI to the services.
- **Frontend**: A Vue 3 component suite located in `resources/js/`.

## Virtual File System (VFS)

VFS databases are stored in `my-data/vfs/`. Each database contains a `vfs_items` table that stores the file structure and contents. This pattern allows for managing "virtual" assets without cluttering the server's actual file system.

## Image Processing

The plugin leverages the core `GaiaAlpha\Media` class to perform server-side image manipulations. The UI provides a real-time preview before saving changes.
