# Code Statistics

Last updated: 2025-12-24

## Overall Project Statistics

```
-------------------------------------------------------------------------------
Language                     files        blank       comment           code
-------------------------------------------------------------------------------
PHP                            245         3813          2070          18525
JavaScript                      95         1665           546          15451
Markdown                       120         2700             2           9466
CSS                              5          623           193           3662
JSON                            44            0             0            727
SQL                             27           29            33            712
HTML                             2           37             0            233
Python                           1           13             5             69
YAML                             2            7            21             68
Dockerfile                       2           11             9             63
Bourne Shell                     2           20            20             62
Text                             1            1             0             34
SVG                              1            0             0              4
-------------------------------------------------------------------------------
SUM:                           547         8919          2899          49076
-------------------------------------------------------------------------------
```

## Breakdown by Component

### Core Framework
- **PHP Backend**: ~7,308 lines of code
- **JavaScript Frontend**: ~5,838 lines of code
- **CSS Styling**: ~2,670 lines of code
- **SQL Migrations**: ~712 lines of code

### Plugin Ecosystem

Total plugins: 28 active plugins

**Top 10 Largest Plugins by Code:**

1. **McpServer** - 2,745 lines
2. **MediaLibrary** - 1,731 lines
3. **GraphsManagement** - 1,580 lines
4. **FileExplorer** - 1,553 lines
5. **NodeEditor** - 1,422 lines
6. **FormBuilder** - 1,159 lines
7. **ComponentBuilder** - 1,055 lines
8. **Todo** - 974 lines
9. **Slides** - 888 lines
10. **Mail** - 846 lines

### Code Quality Metrics

- **Average lines per PHP file**: 75 lines
- **Average lines per JS file**: 162 lines
- **Comment ratio**: 5% (2,899 comments / 49,076 code)
- **Blank line ratio**: 18% (8,919 blank / 49,076 code)

### Recent Additions (2025-12-24)

**Growth:**
- Codebase grew to 49,076 lines
- Enhanced plugin ecosystem

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

*Generated using [cloc](https://github.com/AlDanial/cloc) (excluding external libraries)*
