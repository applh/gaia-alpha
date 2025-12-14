# Template System Evaluation

This document evaluates the state of the Templating System in Gaia Alpha and provides a plan to fully enable users to create page templates and instantiate pages from them.

## 1. Current State Analysis

### Backend (`CmsController.php`, `Template.php`, `Page.php`)
-   **Models**:
    -   `Template` model exists and supports `create`, `update`, `delete`, `findAllByUserId`.
    -   `Page` model includes a `template_slug` column, allowing a page to be linked to a template.
-   **Controllers**:
    -   `CmsController` has endpoints for Templates (`/api/cms/templates`) and Pages (`/api/cms/pages`).
    -   CRUD operations are mostly implemented.
-   **Database**:
    -   `cms_templates` table exists.
    -   `cms_pages` table has `template_slug`.

### Frontend (`CMS.js`, `TemplateBuilder.js`)
-   **Template Builder**:
    -   `TemplateBuilder.js` exists and provides a drag-and-drop interface for creating a structural JSON (header, main, footer).
    -   It supports dragging "Section", "Columns", "Heading", "Paragraph", "Image" into the structure.
    -   The structure is saved as a JSON string in the `content` field of the `cms_templates` table.
-   **CMS Interface**:
    -   `CMS.js` allows creating Templates using `TemplateBuilder`.
    -   `CMS.js` allows creating Pages and **selecting a template** via a dropdown (`form.template_slug`).
    -   However, the Page Editor (`CMS.js` form) currently just shows a generic `textarea` for content. It does **not** interpret or verify the selected template structure.

### The Missing Link: **Page Rendering Logic**
Currently, `PublicController.php` just returns the raw page data (including the `template_slug`). It does **not** combine the Page Content with the Template Structure.
-   The "Content" of a page currently is just one big blob.
-   If a user selects a Template, they likely expect to fill in **slots** defined by that template (e.g., "Header Text", "Main Body", "Footer Link").
-   Currently, `TemplateBuilder` defines a static structure, but doesn't explicitly define "editable regions" vs "static structure", although everything is technically "content".

## 2. Recommendation: "Structure + Content" Approach

To make this useful, we need to distinguish between **Defining the Structure** (Template) and **Filling the Content** (Page).

### Plan

1.  **Refine TemplateBuilder**:
    -   Ensure `TemplateBuilder` saves valid JSON structure. (It appears to do this already).

2.  **Update Page Editor (`CMS.js`)**:
    -   When a Template is selected, the "Content" editing experience should change.
    -   Instead of a single `textarea`, we should probably render a **Form based on the Template**, OR allow "Visual Editing" inside the Template structure.
    -   *Simpler Approach*: Treat the Page Content as the "Main" block, and the Template just wraps it?
        -   **Current Structure**: `TemplateBuilder` has `header`, `main`, `footer` regions.
        -   If we want the Page Content to *inject* into `main`, then the Template should define "Static Header" and "Static Footer" and "Dynamic Main".
    -   *Better Approach*: A Page *inherits* the Template's structure. When editing a Page with a Template, we load the Template's JSON. The user then edits the text/images *within* that structure.

3.  **Frontend Rendering (`public/index.php` or JS Frontend)**
    -   We need a renderer that takes `Page Content` + `Template Structure` and produces HTML.
    -   Since this is a Vue SPA (mostly), the "Public View" currently is just an API response.
    -   We need a **Public Viewer Component** (e.g., `PageViewer.js`) that:
        1. Fecthes Page.
        2. If `page.template_slug` exists, fetches Template.
        3. Merges/Renders them.

### Proposed Workflow
1.  **Create Template**: User uses `TemplateBuilder` to design a layout (Header, Footer, Sections).
2.  **Create Page**:
    -   User selects Template.
    -   System clones the Template's structure into the Page's content (as a starting point).
    -   User edits the content (changing text, images) but keeps the structure.
    -   **Advantage**: Simple. Page effectively "forks" the template.
    -   **Disadvantage**: Updates to Template don't propagate to existing Pages.

### Alternative: Dynamic Linking (More Complex but Better)
1.  **Create Template**: Defines layout.
2.  **Create Page**: Stores `template_slug` and a `content_payload` (JSON).
    -   `content_payload` maps to IDs or zones in the template (e.g., `main_text`, `header_title`).
3.  **Render**:
    -   Load Template.
    -   Inject `content_payload` into Template placeholders.

### Recommended MVP Step
**"Fork" Model (Cloning)**
Since `TemplateBuilder` creates a full JSON structure, the easiest path for the user to "create pages using these templates" is:
1.  When creating a Page, if a Template is selected, **pre-fill** the Page's `content` with the Template's `content` (JSON structure).
2.  Use the **same** `TemplateBuilder` (or a restricted version of it) to edit the Page content. This allows them to change text/images but utilizing the layout they started with.

## 3. Implementation Steps

1.  **Modify `CMS.js`**:
    -   Watch `form.template_slug`.
    -   When it changes (and if Page content is empty), fetch the Template.
    -   Set `form.content` = `template.content`.
    -   **Crucial**: Switch the Page Editor from `textarea` to `TemplateBuilder` (or a new `PageBuilder` wrapper around it) if the content is detected as JSON structure.

2.  **Update `TemplateBuilder.js`**:
    -   Ensure it can operate in "Page Mode" (maybe identical to Template Mode for now).

3.  **Update Public View**:
    -   Create a frontend route/view (e.g., `/page/:slug`) that renders this structure. currently `PublicController::show` returns JSON. We need a frontend component to visualize it.

## 4. Summary
The "building blocks" are there. The system needs the "glue" logic in `CMS.js` to:
1.  Copy Template JSON -> Page Content.
2.  Use the Visual Builder to edit Page Content.
3.  A proper Renderer for the public facing pages.
