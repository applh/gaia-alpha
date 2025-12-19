# MCP Features Roadmap

This document outlines the planned expansion of the Model Context Protocol (MCP) integration for Gaia Alpha. The goal is to evolve the CMS from a simple content store into a fully tool-integrated platform for AI-assisted development and management.

## Current Capabilities (v1.1.0)
- **Tools**: `system_info`, `list_sites`, `create_site`, `list_pages`, `get_page`, `upsert_page`, `db_query`, `list_media`, `read_log`, `verify_system_health`, `backup_site`, `install_plugin`.
- **Resources**: `cms://sites/list`, `cms://system/logs`, `cms://sites/{site}/database/tables`.
- **Prompts**: `summarize_page`.

---

## Future Roadmap

### Phase 1: Advanced Content Operations
Focus on making the AI a more powerful content editor.
- **[ ] SEO Analysis Tool**: Automatically analyze a page's SEO score and suggest improvements based on a target keyword.
- **[ ] AI Image Generation Tool**: Integrated tool to generate assets via external APIs (DALL-E, Stable Diffusion) and save them directly to the site's assets.
- **[ ] Bulk Content Importer**: Tool to import content from external formats (JSON, CSV, or another CMS) into Gaia Alpha pages.
- **[ ] Content Versioning Resource**: Access historical versions of a page for comparison.

### Phase 2: Enhanced Administrative Control
Empower AI agents to handle routine DevOps and management tasks.
- **[ ] User Management Tools**: `create_user`, `update_user_permissions`, `list_users`.
- **[ ] Plugin marketplace/Search**: Tool to search for available (but not yet installed) plugins from a remote repository.
- **[ ] Site Package Explorer**: Resource to list available site packages in `docs/examples` or a dedicated repository.
- **[ ] Health Check Automation**: A prompt that runs `verify_system_health` and `read_log` to summarize the current system status and any active errors.

### Phase 3: Developer & Theme Experience
Assist developers in building and customizing the platform.
- **[ ] Theme/Component Introspection**: Resources to read the source code of active themes and components (`cms://themes/{theme}/components/{name}`).
- **[ ] Template Schema Generator**: Tool to generate or suggest template metadata and configurations based on a natural language description.
- **[ ] Real-time Log Stream**: SSE-based resource for streaming logs to a developer assistant.
- **[ ] DB Migration Assistant**: Tool to generate SQL migration scripts based on changes to a table's schema description.

### Phase 4: Multi-Agent Collaboration & Specialized Prompts
Develop standardized prompts for specific roles.
- **[ ] SEO Specialist Prompt**: Pre-configured prompt with instructions for keyword research and metadata optimization.
- **[ ] Security Auditor Prompt**: Prompt that instructs the AI to check the `db_query` results and logs for potential vulnerabilities or unusual activity.
- **[ ] UI/UX Designer Prompt**: Prompt focused on evaluating component code against accessibility and design system standards.

---

## Technical Considerations
- **Security**: As tools become more powerful (e.g., user management), strict RBAC (Role-Based Access Control) must be enforced within the `McpServer` class.
- **SSE Transport**: Implementation of the SSE transport layer to support web-based AI agents (e.g., a custom admin dashboard tool).
- **Tool Discovery**: Improving how tools are registered so plugins can dynamically add MCP capabilities to the core server.
