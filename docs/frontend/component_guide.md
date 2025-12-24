# Gaia Alpha UI Component Guide

This guide provides usage examples and code snippets for the UI components in the Gaia Alpha design system. All components are located in `resources/js/components/ui/`.

## Table of Contents
1. [Basic](#basic) (Button, Link, Typography, Badge, Divider)
2. [Form Controls](#form-controls) (Input, Checkbox, Radio, Select, Switch, Textarea)
3. [Data Display](#data-display) (Card, DataTable, Tag, TreeView, Pagination, Avatar)
4. [Navigation](#navigation) (Tabs, Breadcrumb, Dropdown, Sidebar)
5. [Feedback](#feedback) (Alert, Spinner, Tooltip, Modal, Toast)

---

## Basic

### Button
Standard interactive button with hover effects and status support.
```javascript
<ui-button type="primary" @click="handleClick">Click Me</ui-button>
<ui-button type="danger" size="small">Delete</ui-button>
```

### Link
Stylized anchors with optional icons and underline modes.
```javascript
<ui-link type="primary" underline="hover" href="/home">Home</ui-link>
<ui-link type="danger" icon="fa fa-trash" @click="handleDelete">Delete</ui-link>
```

### Typography
Consistent text styling for headings and paragraphs.
```javascript
<ui-title level="1">Main Title</ui-title>
<ui-text type="secondary" strong>Secondary bold text</ui-text>
<ui-paragraph spacing="lg">This is a paragraph with large bottom spacing.</ui-paragraph>
```

### Divider
Horizontal or vertical separators.
```javascript
<ui-divider content-position="center">Section Separator</ui-divider>
<ui-divider direction="vertical" />
```

---

## Form Controls

### Checkbox
Standard toggleable checkbox.
```javascript
<ui-checkbox v-model="checked" label="Accept terms" />
```

### Radio & RadioGroup
Mutually exclusive choices.
```javascript
<ui-radio-group v-model="selected">
    <ui-radio value="1" label="Option 1" />
    <ui-radio value="2" label="Option 2" />
</ui-radio-group>
```

### Select
Dropdown selection with single and multiple support.
```javascript
<ui-select 
    v-model="value" 
    :options="[{label: 'A', value: 'a'}, {label: 'B', value: 'b'}]" 
    placeholder="Select an option" 
/>
```

### Switch
Toggle switch for boolean values.
```javascript
<ui-switch v-model="isActive" :labels="{on: 'Enabled', off: 'Disabled'}" />
```

### Textarea
Multi-line text input.
```javascript
<ui-textarea v-model="content" label="Description" :rows="5" />
```

---

## Data Display

### DataTable
Robust component for rendering data arrays.
```javascript
<ui-data-table 
    :data="tableData" 
    :columns="[{label: 'Name', prop: 'name'}, {label: 'Age', prop: 'age'}]"
    :pagination="{ total: 100, pageSize: 10, currentPage: 1 }"
    @page-change="handlePageChange"
>
    <!-- Custom cell rendering -->
    <template #col-name="{ row }">
        <strong>{{ row.name }}</strong>
    </template>
</ui-data-table>
```

### Pagination
Navigation for large data sets.
```javascript
<ui-pagination :total="100" :page-size="10" v-model:currentPage="page" />
```

### Avatar
User profile representation.
```javascript
<ui-avatar src="/path/to/img.png" size="lg" shape="circle" />
<ui-avatar text="John Doe" size="md" />
```

---

## Navigation

### Tabs
Switch between different views.
```javascript
<ui-tabs v-model="activeTab">
    <ui-tab-pane label="General" name="general">General Content</ui-tab-pane>
    <ui-tab-pane label="Config" name="config">Configuration Content</ui-tab-pane>
</ui-tabs>
```

### Breadcrumb
Indicate the current page's location.
```javascript
<ui-breadcrumb separator="/">
    <ui-breadcrumb-item to="/">Home</ui-breadcrumb-item>
    <ui-breadcrumb-item>Current Page</ui-breadcrumb-item>
</ui-breadcrumb>
```

### Dropdown
Trigger-based overlays for action menus.
```javascript
<ui-dropdown trigger="click" @command="handleCommand">
    <template #trigger>
        <ui-button>Actions <i class="fa fa-caret-down"></i></ui-button>
    </template>
    <ui-dropdown-item command="edit">Edit</ui-dropdown-item>
    <ui-dropdown-item command="delete" divided type="danger">Delete</ui-dropdown-item>
</ui-dropdown>
```

---

## Feedback

### Alert
Contextual feedback messages.
```javascript
<ui-alert type="success" title="Success!" description="Operation completed." closable />
```

### Spinner
Loading status indicator.
```javascript
<ui-spinner size="md" />
```

### Tooltip
Hover-based information display.
```javascript
<ui-tooltip text="Click to save">
    <ui-button icon="fa fa-save" />
</ui-tooltip>
```
