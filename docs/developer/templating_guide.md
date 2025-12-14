# Gaia Alpha Templating System Guide

This guide explains how to use the new Templating System to create reusable page layouts.

## Overview

The Templating System allows you to:
1.  **Define Templates**: Create structural layouts using a drag-and-drop builder.
2.  **Create Pages**: Instantiate new pages based on these templates.
3.  **Visual Editing**: Edit the content of these pages using the same visual builder, ensuring adherence to the defined structure.

## How to Use

### 1. Creating a Template
1.  Navigate to **Content > Templates**.
2.  Click **Create**.
3.  Enter a **Title** (e.g., "Standard Blog Post") and **Slug**.
4.  **Define Layout**:
    -   Drag **Sections** or **Columns** into the Main area.
    -   Select a "Columns" item and use the **Properties Panel** to set the number of columns (1-12).
5.  **Define Slots**:
    -   Add placeholders like **Headers** and **Paragraphs**.
    -   Select a placeholder and enter a **"Slot Name"** in the Properties Panel (e.g., "Main Title", "Hero Image").
    -   This name will appear in the Page Editor.
6.  Click **Create** to save.

### 2. Creating a Page from a Template
1.  Navigate to **Content > Pages** (CMS).
2.  Click **Create**.
3.  Enter a **Title** and **Slug**.
4.  In the **Template** dropdown, select your newly created template.
5.  **Slot Editor**:
    -   The editor will automatically show a list of cards corresponding to the **Slots** you defined in the template.
    -   Simply fill in the text or image URLs for each card.
6.  **Advanced Editing**:
    -   If you need to change the structure for this specific page, toggle the **"Edit Structure"** button to switch to the full builder.
7.  Click **Create** to save the page.

### 3. Viewing Pages
-   **Public URL**: Pages are automatically rendered at `/page/[slug]`.
-   **Renderer**: The system converts the JSON structure into valid HTML, respecting columns, headers, and sections.

## Developer Notes
-   **Storage**: Pages store the full structural JSON in the `content` column.
-   **Renderer**: Implemented in `PublicController::render`.
-   **Slot Logic**: `SlotEditor.js` parses the JSON tree to find nodes with a `slotName` property and presents them in a flattened list.

