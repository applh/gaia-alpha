# MCP Tool Pattern

This document outlines how to standardly add tools to the Model Context Protocol (MCP) server. Gaia Alpha uses a fully dynamic discovery mechanism that allows the system to be extended without modifying core files.

## Architectural Overview

MCP tools are individual classes residing in the `McpServer\Tool` namespace. The `McpServer\Server` class dynamically discovers these tools and provides them to the AI agent.

- **Dynamic Discovery**: The `tools/list` response is built by scanning the `plugins/McpServer/class/Tool/` directory.
- **Self-Documenting**: Each tool class provides its own metadata (name, description, schema) via the `getDefinition()` method.
- **Dynamic Resolution**: Tool calls are automatically routed to the corresponding class based on the tool name.

## AI-Assisted Development & Dynamic Powers

PHP is an exceptionally dynamic language, and Gaia Alpha leverages this to enable "Zero-Build" AI-assisted development. By using **PSR-4 Autoloading** and **Dynamic Class Instantiation**, AI agents can extend the system by simply creating a new file.

### 1. Dynamic Tool Discovery & Resolution
The MCP server uses a convention-based approach. A tool named `create_site` is automatically mapped to the class `McpServer\Tool\CreateSite`.

- **Listing**: The server instantiates each class in `Tool/` and calls `getDefinition()` to build the tool registry.
- **Execution**: When a tool is called, the server dynamically instantiates the class and calls `execute()`.

### 2. Zero-Build Extensibility
Because PHP loads files at runtime, an AI agent can:
- **Create a new file**: `plugins/McpServer/class/Tool/NewFeature.php`
- **Immediate Availability**: The tool is instantly ready to be listed and called.
- **Isolation**: New code resides in its own class, avoiding side effects on the `Server` class or other tools.

## Implementing a Tool Class

All tools should extend `McpServer\Tool\BaseTool` and implement two required methods: `getDefinition()` and `execute()`.

### Example: AnalyzeSeo.php

```php
<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\Page;

class AnalyzeSeo extends BaseTool
{
    /**
     * Define the tool's metadata for tools/list
     */
    public function getDefinition(): array
    {
        return [
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
        ];
    }

    /**
     * Handle the tool execution
     */
    public function execute(array $arguments): array
    {
        $slug = $arguments['slug'] ?? null;
        
        // Site context corresponds to the 'site' argument and is switched by Server.php
        // before calling execute(), making Page::findBySlug($slug) multi-site aware.

        $page = Page::findBySlug($slug);
        if (!$page) {
            throw new \Exception("Page not found: $slug");
        }

        // ... logic ...

        return $this->resultJson($report);
    }
}
```

## Plugin-defined Tools (Legacy/External)

While the default pattern is to add classes to `Tool/`, plugins can still contribute tools via hooks.

```php
// In your external plugin's index.php

Hook::add('mcp_tools', function ($result) {
    $result['tools'][] = [
        'name' => 'your_plugin_tool',
        // ... metadata ...
    ];
    return $result;
});

Hook::add('mcp_tool_call', function ($null, $name, $arguments) {
    if ($name === 'your_plugin_tool') {
        // ... execution ...
        return $this->resultText("Done.");
    }
    return $null;
});
```

## Documentation Requirement

New MCP tools, prompts, or resources **must** be documented in:
1.  **`docs/architect/mcp_roadmap.md`**: Update the status and add the new tool to the relevant roadmap phase.
2.  **Plugin/Tool specific docs**: Document the tool's specific purpose and complex behaviors if they exceed the standard metadata description.

## Recommended Design Patterns

1.  **Command Pattern**: Each MCP tool is essentially a **Command** object. It encapsulates a request as an object (the class) with a standard execution method (`execute`). Keep this clear separation.
2.  **Chain of Responsibility**: If a tool needs to perform a sequence of checks (auth, validation, resource existence), consider breaking these into small, chainable steps or using a middleware approach within the tool if it gets complex.
3.  **Data Transfer Object (DTO)**: The `$arguments` array in `execute()` can be unstructured. For complex tools, immediately mapping this array to a strict DTO class (e.g., `SeoAnalysisRequest`) helps enforce types and structure early.

## Checklist

- [x] Class extends `BaseTool`.
- [x] `getDefinition()` returns valid JSON Schema.
- [x] `execute()` handles arguments and performs the action.
- [x] Errors are thrown with descriptive messages.
- [x] Returns structured content using standard helpers.
- [x] Roadmap and tool-specific documentation are updated.

## MCP Prompt Pattern

MCP prompts follow the same dynamic discovery pattern as tools but reside in the `McpServer\Prompt` namespace.

### Architectural Overview

- **Dynamic Discovery**: The `prompts/list` response is built by scanning the `plugins/McpServer/class/Prompt/` directory.
- **Self-Documenting**: Each prompt class provides its own metadata via `getDefinition()`.
- **Dynamic Resolution**: Prompt retrieval (`prompts/get`) is automatically routed to the corresponding class.

### Implementing a Prompt Class

All prompts should extend `McpServer\Prompt\BasePrompt`.

```php
<?php

namespace McpServer\Prompt;

class SummarizePage extends BasePrompt
{
    public function getDefinition(): array
    {
        return [
            'name' => 'summarize_page',
            'description' => 'Summarize the content of a page',
            'arguments' => [
                [
                    'name' => 'slug',
                    'description' => 'Slug of the page to summarize',
                    'required' => true
                ]
            ]
        ];
    }

    public function getPrompt(array $arguments): array
    {
        $slug = $arguments['slug'] ?? 'home';
        return [
            'description' => 'Summarize the content of a page',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        'type' => 'text',
                        'text' => "Please summarize the content of the page with slug '$slug'."
                    ]
                ]
            ]
        ];
    }
}
```

## Checklist (Prompts)

- [x] Class extends `BasePrompt`.
- [x] `getDefinition()` returns valid prompt metadata.
- [x] `getPrompt()` returns the required messages and description.

## MCP Resource Pattern

MCP resources also follow the dynamic discovery pattern and reside in the `McpServer\Resource` namespace.

### Architectural Overview

- **Dynamic Discovery**: The `resources/list` response is built by scanning the `plugins/McpServer/class/Resource/` directory.
- **URI Matching**: Each resource class determines if it handles a specific URI via the `matches(string $uri)` method, which can return regex matches for parameterized URIs like `cms://sites/{site}/database/tables`.
- **Dynamic Reading**: `resources/read` calls are routed to the matching resource class.

### Implementing a Resource Class

All resources should extend `McpServer\Resource\BaseResource`.

```php
<?php

namespace McpServer\Resource;

use GaiaAlpha\SiteManager;

class SitesList extends BaseResource
{
    public function getDefinition(): array
    {
        return [
            'uri' => 'cms://sites/list',
            'name' => 'All Sites',
            'mimeType' => 'application/json'
        ];
    }

    public function matches(string $uri): ?array
    {
        return $uri === 'cms://sites/list' ? [] : null;
    }

    public function read(string $uri, array $matches): array
    {
        $sites = SiteManager::getAllSites();
        return $this->contents($uri, json_encode($sites, JSON_PRETTY_PRINT));
    }
}
```

## Checklist (Resources)

- [x] Class extends `BaseResource`.
- [x] `getDefinition()` returns valid resource metadata.
- [x] `matches(string $uri)` correctly identifies handled URIs and extracts parameters.
- [x] `read(string $uri, array $matches)` returns structured content via `$this->contents()`.
