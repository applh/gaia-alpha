# Drawing Plugin

## Objective
The Drawing plugin provides a versatile canvas for users to create digital artwork and technical drawings. It adapts to the user's skill level by offering three distinct usage modes, ensuring a low barrier to entry for beginners and powerful capabilities for experts.

## Usage Levels

The Drawing plugin features dynamic toolsets based on the selected usage level:

### 1. Beginner Level
Designed for simplicity and quick sketches.
- **Tools**:
    - **Pencil**: Basic freehand drawing.
    - **Eraser**: Simple stroke deletion.
    - **Color Picker**: Basic palette of 8 colors.
    - **Clear Canvas**: Reset the drawing.

### 2. Pro Level
Enhanced for standard creative work and layered designs.
- **Includes all Beginner tools, plus**:
    - **Shapes**: Rectangle, Circle, Line tool.
    - **Layers**: Support for up to 5 layers.
    - **Background Layer**: Select an image from the [Media Library](media_library.md) as a reference layer.
    - **Brush Settings**: Adjustable size and opacity.
    - **Custom Palette**: Full RGB color selection.
    - **Undo/Redo**: Simple history management.

### 3. Expert Level
Full-featured suite for professional-grade illustrations and technical drafts.
- **Includes all Pro tools, plus**:
    - **Vector Paths**: Bezier curve tool.
    - **Advanced Layers**: Unlimited layers with blending modes and persistent background layer.
    - **Filters**: Blur, Sharpen, and Color Adjustment filters.
    - **Snapping**: Grid and object snapping for precision.
    - **Export Options**: SVG, PNG, and PDF export.

## Configuration
The default usage level can be set in `plugin.json` or via environment variables:
- `DRAWING_DEFAULT_LEVEL`: `beginner` | `pro` | `expert` (Default: `beginner`)

## Architecture

### Backend
- **Controller**: `Drawing\Controller\DrawingController`
- **Service**: `Drawing\Service\DrawingService`
- **Model**: `Drawing\Model\Drawing` (JSON-based storage for vector paths and layer metadata)

### Frontend
- **Component**: `plugins/Drawing/resources/js/DrawingCanvas.js`
- **Tech**: Canvas API for raster operations, SVG for vector path rendering.
- **Integration**: Registered via `UiManager` and injected into the "Create" menu group.

## API Endpoints

- `GET /@/drawing/artworks` - List user artworks.
- `GET /@/drawing/artworks/:id` - Fetch artwork data.
- `POST /@/drawing/artworks/save` - Save current artwork (JSON payload).
- `DELETE /@/drawing/artworks/:id` - Delete an artwork.

## Hooks
- `drawing_save_after`: Triggered after a transformation or save operation.
- `drawing_level_changed`: Hook to allow other plugins to react to skill level updates.
