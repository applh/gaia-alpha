# Node Editor Plugin

## Objective
The Node Editor plugin provides a visual, drag-and-drop interface for creating node-based diagrams. It is designed to be a generic tool for modeling processes, data flows, or any connected graph structure.

## Features
- **Infinite Canvas**: Pan and zoom support with a **Minimap** for navigation.
- **Node Types**:
    - **Process**: General purpose processing step.
    - **Input/Output**: Data entry and exit points.
    - **Note**: Text annotations.
- **Connections**: Bezier curve connections between ports.
- **Productivity Tools**:
    - **Selection Box**: Hold Shift and drag to select multiple nodes.
    - **Context Menu**: Right-click for quick actions (Duplicate, Tidy, Delete).
    - **Tidy Layout**: Auto-align nodes to a grid.
- **Power Features**:
    - **Nested Diagrams (Sub-graphs)**: Create modular, drill-down diagrams.
    - **Global Variables**: Manage diagram-wide constants and state.
    - **Export Engine**: Export to SVG, PNG, and Mermaid.js.
    - **Live Search**: Instantly find and teleport to nodes.
    - **Sticky Comments**: Add context with non-intrusive comment blocks.
- **Management**: Create, Save, Load, and Delete diagrams.
- **Improved Deletion**: Dedicated delete buttons for Nodes and Comments with visual feedback.
- **Styling**: Enhanced CSS for comments and action buttons.

## Keyboard Shortcuts
- `N`: Create new Process Node at mouse cursor.
- `D`: Duplicate selected node(s).
- `L`: Auto-align (Tidy) layout.
- `Delete` / `Backspace`: Delete selected items.
- `Esc`: Clear selection and close menus.
- `Shift + Drag`: Selection box.

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

## Roadmap

The Node Editor is evolving into a central hub for logic and data orchestration within Gaia Alpha. The following roadmap outlines the planned phases of development.

### Phase 1: UX & Productivity (Completed ✅)
- **Minimap**: A high-level overview of the canvas for rapid navigation in complex diagrams.
- **Context Menu**: A right-click menu for quick actions (duplicate, delete, disconnect, group).
- **Selection Box**: Drag-to-select multiple nodes for bulk moving and alignment.
- **Tidy Layout**: An "Auto-Layout" button to automatically arrange and space nodes neatly using a Sugiyama-style algorithm.
- **Improved Keyboard Shortcuts**: Full control via keyboard (e.g., `N` for new, `Ctrl+D` for duplicate, `Ctrl+G` for group).

### Phase 2: Power Features (Completed ✅)
- **Nested Diagrams (Sub-graphs)**: Ability to "encapsulate" a group of nodes into a single "Sub-graph" node to reduce complexity.
- **Global Variable Registry**: Define variables at the diagram level that nodes can read from or write to.
- **Export Engine**: Export diagrams to High-Res PNG, SVG, and Mermaid.js markdown for documentation.
- **Live Search**: Locate specific nodes by title or metadata and instantly teleport the view to them.
- **Sticky Comments**: Threaded annotations and sticky notes for collaboration and documentation.

### Phase 3: AI-Assisted Graphing (AI-First)
- **Prompt-to-Graph**: Describe a process in natural language and have the AI generate the initial node structure.
- **Logic Validation**: AI analysis of the graph to detect circular dependencies, dead ends, or logical inconsistencies.
- **Smart Wiring**: Select two nodes, and the AI suggests the most appropriate ports and logic for the connection.
- **Auto-Documentation**: AI generates a technical summary of what the diagram does based on its structure.

### Phase 4: Runtime & Ecosystem (Long-Term)
- **Workflow Execution Engine**: Turn diagrams into executable backend workflows (integrating with the Automation plugin).
- **Real-time Collaboration**: Multi-user editing with visible cursors and live updates.
- **External Data Binding**: "Live" nodes that fetch and display real-time data from the Gaia Alpha API or external webhooks.
- **Plugin API**: A public API allowing other plugins to register custom Node Types, Icons, and Property Inspectors.

