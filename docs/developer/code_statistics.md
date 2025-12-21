# Code Statistics

Last updated: 2025-12-21

## Overall Project Statistics

```
-------------------------------------------------------------------------------
Language                     files          blank        comment           code
-------------------------------------------------------------------------------
PHP                            198           3618           2067          16584
JavaScript                      74           1236            445          10926
Markdown                        97           2293              2           8062
CSS                              4            556            172           3214
SQL                             21             22             13            830
JSON                            37              0              0            735
HTML                             2             37              0            233
Bourne Shell                     2             20             20             62
Dockerfile                       2             11              9             59
Text                             2              1              0             54
YAML                             2              3              1             35
SVG                              1              0              0              4
-------------------------------------------------------------------------------
SUM:                           442           7797           2729          40798
-------------------------------------------------------------------------------
```

## Breakdown by Component

### Core Framework
- **PHP Backend**: ~16,584 lines of code
- **JavaScript Frontend**: ~10,926 lines of code
- **CSS Styling**: ~3,214 lines of code
- **SQL Migrations**: ~830 lines of code

### Plugin Ecosystem

Total plugins: 18 active plugins

**Top 10 Largest Plugins by Code:**

1. **McpServer** - 2,406 lines
   - Model Context Protocol implementation
   - 20+ tools, 5+ resources, and prompts

2. **GraphsManagement** - 1,581 lines
   - Chart.js integration
   - Graph and Collection management

3. **ComponentBuilder** - 1,057 lines
   - Visual component builder
   - Code generation

4. **Todo** - 965 lines
   - Hierarchical Todo list
   - Drag-and-drop, Gantt, and Calendar views

5. **DatabaseConnections** - 689 lines
   - External database management
   - Connection testing and query execution

6. **DatabaseManager** - 494 lines
   - Internal database schema management
   - Table inspection

7. **Map** - 434 lines
   - Interactive maps with markers
   - Location management

8. **Analytics** - 415 lines
   - Visit tracking and statistics
   - Dashboard visualization

9. **ApiBuilder** - 411 lines
   - REST API endpoint generator
   - Dynamic routing and CRUD

10. **Mail** - 399 lines
    - Email sending service
    - Multiple drivers (SMTP, Log, Sendmail)

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
