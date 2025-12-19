# MCP Tool Pattern

This document outlines how to standardly add tools to the Model Context Protocol (MCP) server. Tools allow AI agents to interact with the CMS by executing functions.

## Architectural Overview

MCP tools are registered in the `McpServer\Server` class. They typically wrap existing Controller or Model logic to provide a clean interface for AI.

- **Tools**: Defined in `tools/list`. Each tool has a name, description, and an `inputSchema` (JSON Schema).
- **Tool Handling**: Implemented in `handleToolCall`. This matches the tool name and executes the corresponding logic.
- **Site Isolation**: Always use `$this->switchSite($site)` if the tool interacts with site-specific data (pages, database, assets).

## AI-Assisted Development & Dynamic Powers

PHP is an exceptionally dynamic language, and Gaia Alpha leverages this to enable "Zero-Build" AI-assisted development. By using **PSR-4 Autoloading** and **Dynamic Class Instantiation**, AI agents can extend the system without touching core files.

### 1. Dynamic Tool Resolution
The MCP server uses a convention-based approach to resolve tools. A tool named `create_site` is automatically mapped to the class `McpServer\Tool\CreateSite`.

```php
// In Server.php: handleToolCall
$className = 'McpServer\\Tool\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
if (class_exists($className)) {
    $tool = new $className();
    return $tool->execute($arguments);
}
```

### 2. Zero-Build Extensibility
Because PHP loads files at runtime, an AI agent can:
- **Create a new file**: `plugins/McpServer/class/Tool/NewFeature.php`
- **Immediate Availability**: The tool is instantly ready to be called by the MCP server.
- **Isolation**: New code resides in its own class, avoiding side effects on the `Server` class or other tools.
- **Safety**: Errors are localized. A syntax error in a new tool won't crash the entire MCP server unless that specific tool is invoked.

New tools can be added directly to `Server.php` or via the `mcp_tools` filter hook for plugins.

### In Server.php (`tools/list` case)

```php
[
    'name' => 'analyze_seo',
    'description' => 'Analyze SEO for a specific page',
    'inputSchema' => [
        'type' => 'object',
        'properties' => [
            'slug' => ['type' => 'string', 'description' => 'Page slug'],
            'site' => ['type' => 'string', 'description' => 'Site domain (default: default)'],
            'keyword' => ['type' => 'string', 'description' => 'Target keyword (optional)']
        ],
        'required' => ['slug']
    ]
]
```

### Implementing a Tool Class

All tools should extend `McpServer\Tool\BaseTool`.

```php
<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\Page;

class AnalyzeSeo extends BaseTool
{
    public function execute(array $arguments): array
    {
        $slug = $arguments['slug'] ?? null;
        
        // Ensure site context if necessary
        // $this->switchSite($arguments['site'] ?? 'default'); // Handled by Server.php

        $page = Page::findBySlug($slug);
        if (!$page) {
            throw new \Exception("Page not found: $slug");
        }

        // ... logic ...

        return $this->resultJson($report);
    }
}
```

## Plugin-defined Tools

Plugins should use hooks to avoid modifying core code.

```php
// In your plugin's index.php

Hook::add('mcp_tools', function ($result) {
    $result['tools'][] = [
        'name' => 'your_plugin_tool',
        'description' => 'Description of your tool',
        'inputSchema' => [
            'type' => 'object',
            'properties' => [
                'param' => ['type' => 'string']
            ]
        ]
    ];
    return $result;
});

Hook::add('mcp_tool_call', function ($null, $name, $arguments) {
    if ($name === 'your_plugin_tool') {
        // ... logic ...
        return [
            'content' => [
                ['type' => 'text', 'text' => 'Tool executed successfully.']
            ]
        ];
    }
    return $null;
});
```

## Key Guidelines

1.  **JSON Schema**: Always provide a clear `inputSchema`. It is the only way the AI knows how to call your tool.
2.  **Explicit Errors**: Throw `\Exception` with clear messages. These are passed back to the AI.
3.  **Standard Responses**: Use `$this->resultText($text)` or `$this->resultJson($data)` for consistency.
4.  **Read-Only Safety**: For `db_query` style tools, strictly enforce `SELECT` queries to prevent accidental data corruption.
5.  **Site Context**: Always consider `site` as an optional argument so the AI can operate across different managed sites.

## Checklist

- [ ] Tool is registered in `tools/list` or via `mcp_tools` hook.
- [ ] `inputSchema` accurately describes all parameters.
- [ ] Tool logic is handled in `handleToolCall` or via `mcp_tool_call` hook.
- [ ] `switchSite()` is called if site-specific data is accessed.
- [ ] Errors are thrown with descriptive messages.
- [ ] Returns structured content using standard helpers.
