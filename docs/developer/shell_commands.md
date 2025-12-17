# Shell Commands and Results

## Code Statistics (CLOC)

Evaluate the number of lines of code excluding vendor libraries.

**Command:**
```bash
cloc . --exclude-dir=vendor,cache,uploads,node_modules,.git,docs --not-match-f=package-lock.json
```

**Results (as of 2025-12-10):**
```text
Language                     files          blank        comment           code
-------------------------------------------------------------------------------
PHP                             50            667            130           2927
JavaScript                      19            379            355           2746
CSS                              1            215             48           1277
Markdown                         3             93              0            326
20: SQL                             14              2              9             83
21: JSON                             1              0              0             44
22: Text                             1              1              0             20
-------------------------------------------------------------------------------
SUM:                            89           1357            542           7423
-------------------------------------------------------------------------------
```

## CLI Help

**Command:**
```bash
php cli.php help
```

**Result:**
```text
Usage: php cli.php <command> [arguments]

Commands:
  table:list <table>                  List all rows in a table
  table:insert <table> <json_data>    Insert a row (e.g. '{"col":"val"}')
  table:update <table> <id> <json>    Update a row by ID
  table:delete <table> <id>           Delete a row by ID
  sql <query>                         Execute a raw SQL query
  media:stats                         Show storage stats for uploads and cache
  media:clear-cache                   Clear all cached images
  file:write <path> <content>         Write content to a file in my-data
  file:read <path>                    Read content from a file in my-data
  file:list [path]                    List files in my-data (or subdirectory)
  file:delete <path>                  Delete a file in my-data
  file:move <source> <destination>    Move/rename a file in my-data
  vendor:update                       Update vendor libraries (Leaflet, Vue, Globe.gl, Lucide)
  user:list                           List all users
  user:create <user> <pass> [level]   Create a new user
  user:update-password <user> <pass>  Update a user's password
  user:delete <user>                  Delete a user
  db:export [file]                    Export database to SQL (or specific file)
  db:import <file>                    Import database from SQL file
  db:save                             Snapshot database to my-data/backups/
  save:all                            Save DB snapshot then zip my-data folder
  help                                Show this help message
```

## CLI Architecture

The CLI system uses standardized classes for input and output management.

### Input Management
The `GaiaAlpha\Cli\Input` class provides static methods to access command-line arguments, automatically filtering framework global flags like `--site=`.
- `Input::get(int $index, $default = null)`: Retrieves an argument by its 0-based index (after the command).
- `Input::has(int $index)`: Checks for the existence of an argument.
- `Input::all()`: Returns all arguments as an array.
- `Input::count()`: Returns the number of arguments.

### Output Management
The `GaiaAlpha\Cli\Output` class provides static methods for formatted terminal output with ANSI color support.
- `Output::writeln(string $message, string $color = null)`: Writes a line with optional color.
- `Output::success(string $message)`: Green success message.
- `Output::error(string $message)`: Red error message.
- `Output::info(string $message)`: Cyan/Blue informational message.
- `Output::warning(string $message)`: Yellow warning message.
- `Output::table(array $headers, array $rows)`: Renders data in a neatly aligned table.
- `Output::title(string $text)`: Renders a section title with an underline.
