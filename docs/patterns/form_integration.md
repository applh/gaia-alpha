# Form Builder Integration Pattern

This document describes how to integrate forms created with the Form Builder into components created with the Component Builder.

## Overview

The Form Builder Integration allows you to:
- Create reusable forms using the visual Form Builder
- Embed those forms into custom components
- Automatically handle form rendering and submission
- Maintain backward compatibility with custom form containers

## Architecture

### Components

1. **FormRenderer** ([FormRenderer.js](file:///Users/lh/Downloads/antig/gaia-alpha/resources/js/components/builders/FormRenderer.js))
   - Dynamically renders forms based on Form Builder schemas
   - Handles form submission to the backend
   - Displays success/error messages

2. **ComponentProperties** ([ComponentProperties.js](file:///Users/lh/Downloads/antig/gaia-alpha/resources/js/components/builders/builder/ComponentProperties.js))
   - Provides UI for selecting between custom forms and Form Builder forms
   - Fetches available forms from the API
   - Stores form selection in component properties

3. **ComponentCodeGenerator** ([ComponentCodeGenerator.php](file:///Users/lh/Downloads/antig/gaia-alpha/plugins/ComponentBuilder/class/Service/ComponentCodeGenerator.php))
   - Generates code that imports FormRenderer
   - Renders FormRenderer when a Form Builder form is selected
   - Falls back to custom form container for legacy components

## Usage

### Creating a Form

1. Navigate to the Form Builder in the admin panel
2. Create a new form with your desired fields
3. Save the form

### Adding a Form to a Component

1. Open the Component Builder
2. Create a new component or edit an existing one
3. Add a "Form Container" component from the toolbox
4. In the properties panel:
   - Select **"Form Builder"** as the Form Source
   - Choose your form from the dropdown
5. Save the component

### How It Works

When you select a Form Builder form:
- The component stores `formSource: 'builder'` and `formId: <id>` in its properties
- During code generation, the ComponentCodeGenerator detects this configuration
- It generates `<FormRenderer :formId="<id>" />` instead of a basic form container
- The FormRenderer fetches the form schema and handles rendering/submission

### Custom Forms (Legacy)

If you select "Custom Form" as the Form Source:
- The component behaves as before
- You can set a custom action URL and HTTP method
- You manually add form fields as child components

## API Endpoints

The integration uses the following endpoints:

- `GET /@/forms` - List all forms (used by ComponentProperties)
- `GET /@/forms/{id}` - Get a specific form by ID (used by FormRenderer)
- `POST /@/public/form/{slug}` - Submit form data (used by FormRenderer)

## Example

### Form Builder Form

```json
{
  "id": 1,
  "title": "Contact Form",
  "slug": "contact",
  "schema": [
    {"key": "name", "type": "text", "label": "Name", "required": true},
    {"key": "email", "type": "email", "label": "Email", "required": true},
    {"key": "message", "type": "textarea", "label": "Message", "required": true}
  ],
  "submit_label": "Send Message"
}
```

### Component Configuration

```json
{
  "type": "form",
  "props": {
    "formSource": "builder",
    "formId": "1"
  }
}
```

### Generated Code

```javascript
<FormRenderer :formId="1" />
```

## Benefits

- **Reusability**: Create a form once, use it in multiple components
- **Consistency**: All forms follow the same styling and behavior
- **Maintainability**: Update a form in one place, changes reflect everywhere
- **Separation of Concerns**: Form logic is separate from component layout
- **Backward Compatibility**: Existing custom forms continue to work

## Future Enhancements

Potential improvements to this pattern:

- Form validation rules in the Form Builder
- Conditional field visibility
- Multi-step forms
- File upload support
- Integration with email notifications
- Custom success/error handlers
