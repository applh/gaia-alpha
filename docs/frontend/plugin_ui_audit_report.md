# Plugin UI Audit Report

This report summarizes the findings from an audit of existing plugins' UI implementations. The goal is to identify opportunities to replace custom or inconsistent UI patterns with the newly implemented Gaia Alpha Design System.

## Summary of Findings

Overall, most plugins were developed prior to the standardization of UI components. Consequently, they exhibit:
1.  **Duplicate CSS**: Many plugins define their own button, input, and card styles.
2.  **Inconsistent Layouts**: Hardcoded margins/paddings and varying grid implementations.
3.  **Fragmented Logic**: Custom modal states and pagination logic in multiple files.

---

## Plugin-Specific Audit

### 1. Todo Plugin
*   **Current State**: Extensive custom UI for list, calendar, and gantt views.
*   **Upgrade Opportunities**:
    *   **Tabs**: Replace custom tab buttons with `ui-tabs`.
    *   **Forms**: Standardize inputs/selects with `ui-input` and `ui-select`.
    *   **Grid**: Use `ui-row` and `ui-col` for the project layout.
    *   **Feedback**: Use `ui-modal` for the edit form.
    *   **Data Display**: Use `ui-tag` for todo labels.

### 2. Ecommerce Plugin
*   **Current State**: Lightweight UI using Tailwind utility classes.
*   **Upgrade Opportunities**:
    *   **Layout**: Wrap sections in `ui-container` and `ui-card`.
    *   **Data Display**: Replace inline price tags with `ui-badge` or `ui-tag`.
    *   **Forms**: Standardize checkout fields with `ui-input` and `ui-textarea`.

### 3. Audit Trail Plugin
*   **Current State**: Traditional HTML table with manual pagination.
*   **Upgrade Opportunities**:
    *   **Data Table**: REPLACE the entire log table with `ui-data-table`.
    *   **Pagination**: Integrate `ui-pagination`.
    *   **Feedback**: Replace manual overlay with `ui-modal`.

### 4. Form Builder Plugin
*   **Current State**: Custom table with `SortTh` component.
*   **Upgrade Opportunities**:
    *   **Data Table**: Upgrade to `ui-data-table` to remove the need for manual `SortTh` logic in the component.
    *   **Navigation**: Use `ui-button` for consistent action groups.

### 5. LMS Plugin
*   **Current State**: Simple grid layout.
*   **Upgrade Opportunities**:
    *   **Grid**: Use `ui-row` and `ui-col` with the `gutter` prop for a better layout.
    *   **Basic**: Standardize typography using `ui-title` and `ui-paragraph`.

---

## General Recommendations

1.  **Phased Migration**: Start with high-impact components like `DataTable` and `Modal` which provide the most visual consistency and code reduction.
2.  **CSS Cleanup**: Once a plugin is upgraded, ensure its custom CSS is audited and removed from `site.css` or local style blocks if it's no longer used.
3.  **Global UI Integration**: Encourage plugin developers to use `ui/` components by default in new plugins.
