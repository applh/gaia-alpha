# Gaia Alpha Design System

 This document outlines the core design principles, CSS architecture, and standard page templates used in the Gaia Alpha administration panel.

 ## 1. CSS Architecture (`site.css`)

 The CSS is organized into 5 layers:

 1.  **Tokens & Variables**: Colors, spacing, typography, radiance, and glassmorphism effects.
 2.  **Reset & Base**: Global defaults and dark mode structure.
 3.  **Layout**: Core layout classes (`.admin-page`, `.admin-grid`, `.admin-header`).
 4.  **Components**: Reusable UI elements (`.btn`, `.card`, `.table`, `.input`, `.switch`).
 5.  **Utilities**: Helper classes for alignment and spacing.

 ### Key Tokens

 | Category | Variable | usage |
 | :--- | :--- | :--- |
 | **Colors** | `--bg-primary` | Main background (gradient/dark) |
 | | `--card-bg` | Component background (glassmorphic) |
 | | `--accent-color` | Primary action color (Purple/Blue) |
 | | `--text-primary` | Main text color |
 | **Effects** | `--glass-bg` | Semi-transparent background for items |
 | | `--glass-blur` | `backdrop-filter: blur(12px)` |

 ## 2. Standard Components

 ### Buttons
 Use standard classes for all buttons:
 -   `btn`: Base class
 -   `btn-primary`: Main call to action
 -   `btn-secondary`: Alternative actions / Neutral
 -   `btn-danger`: Destructive actions
 -   `btn-small`: Compact buttons for tables/toolbars
 -   `btn-icon`: Circular buttons for icons

 ### Cards
 -   `.admin-card`: Standard container for content blocks. Automatically applies glassmorphism, padding, and borders.

 ## 3. Page Templates

 We use consistent layouts ("templates") for all admin pages. When creating a new page, choose the appropriate pattern.

 ### A. Standard List View
 Used for managing sets of resources (Users, Forms, APIs).

 **Structure:**
 1.  **container**: `.admin-page`
 2.  **header**: `.admin-header`
     *   Left: `.page-title` (Grouped with Icon)
     *   Right: Primary Action Button (e.g., "Add New")
 3.  **content**: `.admin-card` -> `table`
     *   Use `SortTh` components for headers.
     *   Use `.btn-small` for row actions.

 **Example (`UsersAdmin.js`):**
 ```html
 <div class="admin-page">
     <div class="admin-header">
         <h2 class="page-title">Users</h2>
         <button class="btn btn-primary">Add User</button>
     </div>
     <div class="admin-card">
         <table>...</table>
     </div>
 </div>
 ```

 ### B. Form / Settings View
 Used for configuration or editing single resources.

 **Structure:**
 1.  **container**: `.admin-page`
 2.  **grid**: `.admin-grid` (Responsive Grid)
 3.  **cards**: `.admin-card` per section
     *   Header: `.card-header` (Optional title/action)
     *   Body: `.form-group` (Inputs)

 **Example (`UserSettings.js`):**
 ```html
 <div class="admin-page">
     <div class="admin-header">...</div>
     <div class="admin-grid">
         <div class="admin-card">
             <h3>Profile</h3>
             <div class="form-group">...</div>
         </div>
     </div>
 </div>
 ```

 ### C. Split View (Map / Specialist)
 Used when a large viewport is needed alongside controls.

 **Structure:**
 1.  **container**: `.admin-page.map-page`
 2.  **header**: `.admin-header` (Grouped Title & Controls)
 3.  **layout**: `.map-layout` (CSS Grid)
     *   Left: `.map-viewport` (Main visualization)
     *   Right: `.map-sidebar` (Controls/List)

 **Example (`MapPanel.js`):**
 ```html
 <div class="admin-page map-page">
     <div class="admin-header">...</div>
     <div class="map-layout">
         <div class="map-viewport">...</div>
         <div class="map-sidebar admin-card">...</div>
     </div>
 </div>
 ```

 ### D. Dashboard View
 Used for high-level overview and stats.

 **Structure:**
 1.  **container**: `.admin-page`
 2.  **grid**: `.stats-grid`
     *   Items: `.stat-card` (Big numbers, icons)

 ## 4. Header Standardization
 All headers must follow this flexbox pattern to ensure alignment:

 ```html
 <div class="admin-header">
     <div style="display:flex; align-items:center; gap:20px;">
         <h2 class="page-title" style="display: flex; align-items: center;">
             <span style="display: inline-flex; margin-right: 12px;">
                 <LucideIcon ... />
             </span>
             Title
         </h2>
         <div class="button-group">
            <!-- Header Level Controls -->
         </div>
     </div>
     <!-- Right Aligned Actions (Optional) -->
 </div>
 ```
