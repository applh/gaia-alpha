# Design System & UI Architecture

## Overview

The Gaia Alpha design system is built on a foundation of "Elegant Modernity." It emphasizes refined typography, a sophisticated "Zinc/Slate" color palette, and subtle glassmorphism effects to create a premium, content-first experience.

## Core Design Principles

1.  **Elegance & Minimalism**: Use whitespace effectively, avoid clutter, and stick to a restrained color palette.
2.  **Visual Hierarchy**: Use typography (weight, size, color) to guide the eye. Primary actions are bold and vibrant; secondary actions are subtle.
3.  **Glassmorphism**: Use translucent backgrounds (`backdrop-filter: blur`) for panels and headers to create depth and context.
4.  **Consistency**: Reusable components (`Icon`, `SortTh`, `Card`) and standardized CSS variables ensure a unified look.

## CSS Architecture (`www/css/site.css`)

The CSS is organized hierarchically to maintain scalability and order.

### 1. Variables (`:root`)
We use CSS variables for all themeable values.
*   **Colors**: `bg-color`, `card-bg`, `text-primary`, `border-color`.
*   **Accents**: `accent-color` (Indigo/Violet), `success-color` (Emerald), `danger-color` (Red).
*   **Spacing**: `space-xs` to `space-xl`.
*   **Radius**: `radius-sm` to `radius-xl`.
*   **Effects**: shadows, glass-blur.

### 2. Global Resets & Typography
*   **Font**: 'Outfit', sans-serif.
*   **Body**: Dark mode by default (`zinc-950`), fixed background gradient for subtle texture.

### 3. Layout Primitives
*   `#app`: Main container.
*   `.app-container`: Flex structures for sidebar/topbar layouts.
*   `.admin-page`: Standard padding and fade-in animation for admin views.

### 4. Components
*   **Headers (`.admin-header`)**:
    *   **Layout**: `justify-content: space-between`.
    *   **Left Zone**: Page Title + Primary Actions (Buttons).
    *   **Right Zone**: Navigation Tabs / Menus.
*   **Cards (`.admin-card`, `.card`)**:
    *   **Style**: Glass background, subtle border, shadow on hover.
    *   **API Builder Specific**: `.api-manager .card` uses distinct borders and margins for readability in grid layouts.
*   **Buttons**: `primary`, `secondary`, `danger`, `ghost`.
*   **Tables**: Clean, bordered rows, hover effects.

## Layout Patterns

### Admin Panels
The standard admin panel structure is:

```html
<div class="admin-page">
    <div class="admin-header">
        <!-- LEFT: Context & Actions -->
        <div class="header-left-group">
            <h2 class="page-title"><Icon /> Title</h2>
            <div class="primary-actions">
                <button class="btn-primary">Action</button>
            </div>
        </div>

        <!-- RIGHT: Navigation -->
        <div class="nav-tabs">
            <button>Tab 1</button>
            <button>Tab 2</button>
        </div>
    </div>

    <!-- Content -->
    <div class="admin-card">...</div>
</div>
```

### Grid Layouts
For collections (like API endpoints), we use variable-width grids:
```css
.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--space-lg);
}
```

## Iconography

*   **Library**: [Lucide Icons](https://lucide.dev/).
*   **Implementation**:
    *   **Local Source**: `www/js/vendor/lucide.min.js`.
    *   **Component**: `<LucideIcon name="icon-name" size="24" />` (Vue component wrapper).
*   **Usage**: All UI icons (navigation, actions, stats) must use Lucide for consistency. Emojis are deprecated for UI elements.

## Vendor Management

Vendor libraries are managed via CLI to ensure stability and offline capability.
*   **Command**: `php cli.php vendor:update`
*   **Path**: `www/js/vendor/`
*   **Config**: `class/GaiaAlpha/Cli/VendorCommands.php`
