# MCP (Model Context Protocol) Integration Proposal

> [!NOTE]
> **Status: Implemented (v0.56.0)**
> The Model Context Protocol is now a core feature of Gaia Alpha, enabling full AI-assisted management and development.

## Introduction
This document outlines the value proposition and architectural plan for the **Model Context Protocol (MCP)** plugin in Gaia Alpha.

Integrating MCP transforms the CMS from a passive application into a **tool-enabled platform**. It allows external AI agents (such as Claude Desktop, Cursor, or IDE-integrated assistants) to securely connect to the CMS, read its data, and execute commands on behalf of the user.

## Core Concept
Instead of a user manually copying data between the CMS and an AI chat interface, the MCP plugin exposes the CMS's internal API as:
1.  **Tools**: Executable functions (e.g., `create_page`, `db_query`).
2.  **Resources**: Read-only data access (e.g., `cms://logs/error.log`, `cms://sites/all`).
3.  **Prompts**: Reusable prompt templates stored within the CMS.

## Use Cases

### 1. Content Operations (The "AI Editor")
**Goal**: Automate content creation and editing workflows.
*   **Scenario**: A user instructs an AI agent: *"Read the 'About Us' page, rewrite the history section to be more concise, and save the changes."*
*   **MCP Tools**:
    *   `read_page(site_id, path)`: Retrives raw page content (Markdown/HTML).
    *   `update_page(site_id, path, content)`: Writes updated content back to the system.
    *   `list_media(site_id)`: content-aware image selection.
*   **Value**: Eliminates copy-pasting; enables bulk editing operations (e.g., *"Fix typo X on all 50 pages"*).

### 2. Deep Data Analysis & RAG
**Goal**: Enable "Chat with your Data" capabilities without manual exports.
*   **Scenario**: *"Analyze the contact form submissions from last week and summarize the top 3 user requests."*
*   **MCP Resources**:
    *   `cms://sites/{site}/database/tables/forms`: Direct read access to form data.
    *   `cms://system/logs`: Read access to system logs for debugging.
*   **Value**: Provides AI with real-time, ground-truth data for accurate analysis and retrieval-augmented generation.

### 3. Automated Site Administration ("DevOps")
**Goal**: Automate complex configuration tasks via natural language.
*   **Scenario**: *"Create a new staging site, import the 'Enterprise' package, and install the Map plugin."*
*   **MCP Tools**:
    *   `create_site(domain, package_source)`
    *   `install_plugin(plugin_name)`
    *   `backup_site(site_id)`
    *   `verify_system_health()`
*   **Value**: Reduces multi-step UI interactions into single-sentence commands; simplifies onboarding for non-technical users.

### 4. Code & Theme Assistance
**Goal**: Assist developers in building themes and components.
*   **Scenario**: *"I want to create a testimonial component. Check the existing 'Card' component to match the style."*
*   **MCP Resources**:
    *   `cms://themes/{theme}/components`: Read access to component code.
    *   `cms://system/docs`: Access to internal developer documentation.
*   **Value**: Context-aware coding assistance that understands the specific architectural nuances of the project.

### 5. Centralized Prompt Management
**Goal**: Use the CMS to distribute standard AI prompts to the team.
*   **Scenario**: An editor connects their AI client to the CMS. They instantly see a prompt called "Brand Guidelines Checker."
*   **MCP Prompts**:
    *   `check_brand_voice`: Automatically loads the company's style guide and validates the current text.
    *   `generate_seo_metadata`: Loads the specific SEO rules defined in the CMS.
*   **Value**: Ensures consistency across the organization by centralizing "AI instructions" alongside the content.

## Technical Implementation

### Architecture
As a PHP application, Gaia Alpha can implement MCP via:
1.  **Stdio Transport (Local)**: A CLI command `php cli.php mcp:server` that talks to local agents (like Claude Desktop) via standard input/output.
2.  **SSE Transport (Remote)**: An endpoint `/@/mcp/sse` using Server-Sent Events for web-based agents.

### Security
*   **Authentication**: Re-use Gaia Alpha's existing API Token or Session capability.
*   **Authorization**: Ensure the MCP Server runs with the same permissions as the authenticated user (e.g., an Editor cannot delete sites via MCP).

### Proposed Plugin Structure
```
plugins/McpServer/
├── plugin.json
├── class/
│   ├── Server.php          # Main MCP Server logic & tool dispatcher
│   ├── Tool/               # Individual Tool classes (dynamic loading)
│   │   ├── BaseTool.php    # Shared logic
│   │   └── GetPage.php     # Example tool
│   └── Transport/          # Stdio/SSE handling
└── verify_mcp.php          # verification script
```

### Dynamic Powers
Gaia Alpha leverages PHP's dynamic nature to provide a "Zero-Build" extension model. New MCP tools are implemented as standalone classes and are automatically discovered and instantiated by the `Server` at runtime, ensuring strict isolation and rapid iteration.
