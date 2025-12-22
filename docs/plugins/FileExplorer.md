# File Explorer Plugin

The **File Explorer** plugin provides a web-based interface for managing files and directories within the Gaia Alpha CMS data directory. It supports both the real filesystem and Virtual File Systems (VFS) stored in SQLite databases.

## Features

- **File Management**: Create, read, update, delete, rename, and move files and folders.
- **Dual Mode**:
    - **Real FS**: Manage files in the `my-data` directory.
    - **Virtual FS**: Manage isolated file systems stored in `.sqlite` files.
- **Media Support**:
    - **Image Editor**: View and edit images (resize, crop, filter).
    - **Video Support**: Play videos and perform basic editing (trim, extract frame).
- **Code Editor**: Syntax highlighting for various file types.
- **JSON Editor**: Visual tree-based editor for JSON files.

## JSON Editor

The integrated JSON Editor allows you to modify `.json` files using a visual tree interface.

### How to use
1.  Open any `.json` file in the File Explorer.
2.  Toggle between **Code** and **Tree** views using the buttons in the editor toolbar.
3.  **In Tree View**:
    - **Expand/Collapse**: Click the chevron icons to reveal or hide nested objects/arrays.
    - **Edit Keys/Values**: Double-click on a key or value to edit it in place.
    - **Reorder**: Drag and drop items to reorder them (currently supported within arrays).
    - **Add Items**: Hover over an item and click the **+** button to add a child (for objects/arrays).
    - **Delete Items**: Hover over an item and click the trash icon to remove it.
    - **Format**: Click the "Format" button to pretty-print the JSON.

## API Endpoints

- `GET /@/file-explorer/list`: List files and folders.
- `GET /@/file-explorer/read`: Read file content.
- `POST /@/file-explorer/write`: Save file content.
- `POST /@/file-explorer/create`: Create a new file or folder.
- `POST /@/file-explorer/delete`: Delete a file or folder.
- `POST /@/file-explorer/rename`: Rename a file or folder.
- `POST /@/file-explorer/move`: Move a file or folder.
- `GET /@/file-explorer/vfs`: List available VFS databases.
- `POST /@/file-explorer/vfs`: Create a new VFS database.
