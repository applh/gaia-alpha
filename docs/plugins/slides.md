# Slides Plugin

The Slides plugin allows users to create, edit, and play interactive slide decks. Each slide is currently a drawing canvas, but the architecture supports future expansion to other types (e.g., Markdown).

## Features

- **Slide Decks**: Group multiple slides into a single deck.
- **Drawing Canvas**: Full-featured drawing tool for each slide with palette and brush size controls.
- **Slide Management**: Add, delete, and reorder slides visually.
- **Deck Management**: Create and delete slide decks.
- **Full-screen Player**: Play presentations with keyboard navigation (Right Arrow/Space to advance, Left Arrow to go back).
- **Auto-save**: Content is saved locally and periodically to the server.

## Architecture

The plugin follows the standard Gaia Alpha plugin pattern:

- **Namespace**: `Slides`
- **Controller**: `Slides\Controller\SlidesController` handling API routes under `/@/slides/`.
- **Service**: `Slides\Service\SlidesService` for business logic and DB interaction.
- **Models**:
    - `SlideDeck`: Represents a presentation container.
    - `SlidePage`: Represents an individual slide/page.

## Database Schema

- `cms_slide_decks`: Stores deck metadata.
- `cms_slide_pages`: Stores individual slide content (JSON/DataURI) and order.

## API Endpoints

| Method | Path | Description |
| :--- | :--- | :--- |
| GET | `/@/slides/list` | Returns all slide decks. |
| GET | `/@/slides/deck/:id` | Returns metadata for a specific deck. |
| POST | `/@/slides/deck/save` | Creates or updates a deck. |
| DELETE | `/@/slides/deck/:id` | Deletes a deck and its pages. |
| GET | `/@/slides/deck/:id/pages` | Returns all pages for a deck. |
| POST | `/@/slides/deck/:id/pages/add` | Adds a new page to a deck. |
| POST | `/@/slides/pages/:id/update` | Updates page content/type. |
| DELETE | `/@/slides/pages/:id` | Deletes a page. |
| POST | `/@/slides/deck/:id/pages/reorder` | Updates the order of pages. |

## Usage

1. Open the **Create** menu in the admin sidebar.
2. Select **Slides**.
3. Create a new deck.
4. Use the sidebar to add and reorder slides.
5. Draw on the canvas.
6. Click **Play** for full-screen presentation mode.
