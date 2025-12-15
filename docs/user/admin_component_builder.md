# Admin Component Builder - User Guide

The **Admin Component Builder** allows you to create custom admin pages and dashboards without writing code. You can drag and drop components, configure them, and publish them to your admin menu.

## Accessing the Builder
1. Log in to the Admin Panel.
2. Navigate to **System > Component Builder**.
3. You will see a list of existing custom components.
4. Click **Create New** to start a fresh component.

## Using the Builder Interface

The builder interface is divided into three main areas:

### 1. Toolbox (Left)
Contains all available components categorized by function:
- **Data Display**: Tables, Stat Cards, Lists.
- **Input**: Forms, Inputs, Selects, Buttons.
- **Visualization**: Bar Charts, Line Charts.
- **Actions**: Action Buttons, Link Buttons.
- **Layout**: Containers, Rows, Columns.

### 2. Canvas (Center)
The main workspace where you design your page.
- **Drag & Drop**: Drag items from the Toolbox onto the Canvas.
- **Reorder**: Drag items within the Canvas to rearrange them.
- **Select**: Click an item to view its settings in the Properties panel.
- **Delete**: Hover over an item and click the "x" to remove it.

### 3. Properties Panel (Right)
Configure the selected component.
- **Label**: The text displayed on the component.
- **Data Source**: (For tables/charts) The API endpoint to fetch data from.
- **Appearance**: Colors, sizes, variants (Primary/Secondary).
- **Layout**: Width, Fluidity, Gutters (for layout components).

## Creating a Dashboard Example

1. Drag a **Container** to the canvas. In properties, set "Fluid Width" to "True" for full width.
2. Inside the container, drag a **Row**.
3. Inside the row, drag two **Columns**. Set each to "Width: 6" (half width).
4. In the first column, drag a **Stat Card**. Set Label to "Total Sales" and Value to "$50,000".
5. In the second column, drag a **Bar Chart**. Set Title to "Sales Trends".
6. Click **Save Component**.

## Actions & Logic
- **Action Button**: Triggers a system event (e.g., `refresh` to reload data).
- **Link Button**: Navigates to another URL (e.g., internal admin page or external site).
- **Forms**: Form containers handle submission automatically to the configured endpoint.

## Preview & Publish
- Click **Save** to generate the component code.
- Your new component will automatically appear in the **Custom** section of the main Admin Menu.
