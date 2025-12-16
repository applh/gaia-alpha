# Example Plugin

This is a simple plugin to demonstrate how to extend Gaia Alpha.

## Features
- Adds a `<meta name="generator">` tag to the head of all public pages.
- Adds a "Powered by Gaia Alpha" message to the footer of all public pages.

## Installation

1. **Copy the Plugin**:
   Copy this entire folder (`example_plugin`) to your `my-data/plugins/` directory.
   
   ```bash
   cp -r docs/example_plugin my-data/plugins/simple_demo
   ```

2. **Activate the Plugin**:
   - Log in to the Admin Panel.
   - Go to **System > Plugins**.
   - You should see "simple_demo" in the list.
   - If it's not active, click "Active" to toggle it on.

3. **Verify**:
   - Visit your public homepage.
   - Check the footer for the "Powered by" message.
   - View Page Source to see the meta tag.

## Directory Structure
```
my-data/plugins/
└── simple_demo/
    ├── index.php         # Main entry point (hooks)
    └── README.md         # This file
```
