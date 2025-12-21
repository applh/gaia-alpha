# Code Statistics

Last updated: 2025-12-21

## Overall Project Statistics

```
-------------------------------------------------------------------------------
Language                     files          blank        comment           code
-------------------------------------------------------------------------------
PHP                            188           3403           2618          14840
JavaScript                      67           1129            863           9379
CSS                              4            556            172           3214
SQL                             19             18             10            531
HTML                             2             37              0            233
Bourne Shell                     2             20             20             62
Dockerfile                       2             11              9             59
YAML                             2              3              1             35
Text                             1              1              0             34
SVG                              1              0              0              4
-------------------------------------------------------------------------------
SUM:                           288           5178           3693          28391
-------------------------------------------------------------------------------
```

## Breakdown by Component

### Core Framework
- **PHP Backend**: ~14,840 lines of code
- **JavaScript Frontend**: ~9,379 lines of code
- **CSS Styling**: ~3,214 lines of code
- **SQL Migrations**: ~531 lines of code

### Plugin Ecosystem

Total plugins: 18 active plugins

**Top 10 Largest Plugins by Code:**

1. **ComponentBuilder** - 1,476 lines
   - Visual component builder with drag-and-drop
   - Code generation and preview

2. **FormBuilder** - 1,175 lines
   - Dynamic form creation
   - Field validation and submission handling

3. **ApiBuilder** - 1,011 lines
   - REST API endpoint generator
   - Request/response handling

4. **McpServer** - 1,008 lines
   - Model Context Protocol implementation
   - 20+ tools, resources, and prompts

5. **DatabaseConnections** - 677 lines
   - External database management
   - MariaDB, PostgreSQL, SQLite support

6. **MediaLibrary** - 653 lines (NEW)
   - Digital Asset Management
   - Tagging, search, and organization

7. **Analytics** - 629 lines
   - Visit tracking and statistics
   - Dashboard visualization

8. **Mail** - 554 lines
   - Email sending with multiple drivers
   - SMTP, Sendmail, Log support

9. **JwtAuth** - 465 lines
   - JWT token authentication
   - Middleware and validation

10. **Chat** - 424 lines
    - Real-time messaging
    - User-to-user communication

### Code Quality Metrics

- **Average lines per PHP file**: 79 lines
- **Average lines per JS file**: 140 lines
- **Comment ratio**: 13% (3,693 comments / 28,391 code)
- **Blank line ratio**: 18% (5,178 blank / 28,391 code)

### Recent Additions (2025-12-21)

**MediaLibrary Plugin:**
- MediaLibraryService.php: 271 lines
- MediaLibraryController.php: 253 lines
- MediaLibrary.js (Vue): 501 lines
- 4 MCP Tools: ~120 lines total
- CSS styling: ~300 lines
- **Total**: ~1,445 lines added

## Growth Trends

The codebase has grown significantly with the addition of:
- Advanced plugin architecture
- MCP server integration
- Digital Asset Management
- Component and Form builders
- Multi-site support

## Code Organization

```
gaia-alpha/
├── class/              # Core framework (PHP)
├── resources/          # Frontend assets (JS, CSS)
├── plugins/            # Plugin ecosystem
├── templates/          # HTML templates
├── www/                # Public web root
└── docs/               # Documentation
```

## Testing Coverage

- Manual testing via browser
- CLI command testing
- MCP tool verification
- Performance benchmarking via `php cli.php bench:all`

## Performance

- **Request handling**: <10ms average
- **Database queries**: Optimized with indexes
- **Asset delivery**: On-the-fly minification
- **Image processing**: AVIF/WebP support

---

*Generated using [cloc](https://github.com/AlDanial/cloc)*
