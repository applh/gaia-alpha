# Node Editor Plugin

## Objective
The Node Editor plugin provides a visual, drag-and-drop interface for creating node-based diagrams. It is designed to be a generic tool for modeling processes, data flows, or any connected graph structure.

## Features
- **Infinite Canvas**: Pan and zoom support.
- **Node Types**:
    - **Process**: General purpose processing step.
    - **Input/Output**: Data entry and exit points.
    - **Note**: Text annotations.
- **Connections**: Bazier curve connections between ports.
- **Management**: Create, Save, Load, and Delete diagrams.

## Configuration
No special configuration is required. The plugin uses a SQLite-compatible schema (`cms_node_diagrams`) which is auto-compatible with MySQL and PostgreSQL via the Gaia Alpha Database layer.

## Architecture

### Backend
- **Controller**: `NodeEditor\Controller\NodeEditorController` handles API endpoints.
- **Service**: `NodeEditor\Service\NodeEditorService` manages database interactions.
- **Storage**: JSON blobs stored in the `content` column of `cms_node_diagrams`.

### Frontend
- **Component**: `plugins/NodeEditor/resources/js/NodeEditor.js`
- **Tech**: Vue 3 (Composition API), SVG for connections, HTML/CSS for nodes.
- **Integration**: Registered via `UiManager` and injected into the "Tools" admin menu.

## API Endpoints

- `GET /@/node_editor/diagrams` - List all diagrams
- `GET /@/node_editor/diagrams/:id` - Get full diagram content
- `POST /@/node_editor/diagrams/save` - Create or Update
- `DELETE /@/node_editor/diagrams/:id` - Delete

## Future Improvements
- Custom node types via plugin extensions.
- Execution engine to run the diagrams.
- Real-time collaboration.
