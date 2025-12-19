# Advanced Prompting: Binary & Archive Context

When using an AI agent (especially in an agentic workflow) to recreate or extend Gaia Alpha, you can significantly improve accuracy by provide binary or structured archive context.

## 1. Using ZIP Archives
Providing a ZIP of the codebase is the best way to communicate the "Physical Blueprint" of the app.

- **How it works**: Most agentic AIs can use terminal tools (`unzip`, `ls -R`) to extract and explore the archive. This preserves the directory hierarchy, which is critical for Gaia Alpha's plugin and autoloader logic.
- **Best Practice (The "Curated ZIP")**: To avoid overwhelming the AI with noise, remove `vendor/`, `node_modules/`, and large media assets before zipping.
- **Example Prompt**:
  > "I have attached a ZIP of the `plugins/Console` directory. Analyze the `plugin.json` and `class/Commands/` patterns, then create a new `plugins/Scheduler` that follows the same standard for command registration."

## 2. Using SQLite Databases
The database schema is the "Source of Truth" for your data models.

- **How it works**: AI agents can run `sqlite3` commands to inspect tables, schemas, and sample data. This is faster and more accurate than asking the AI to "read the PHP code to figure out the table columns."
- **Why it matters**: It ensures the AI generates SQL queries and Repository methods that exactly match your database.
- **Example Prompt**:
  > "Using the attached `database.sqlite` file, inspect the `users` and `site_metadata` tables. Generate a `SiteSettingsController` that can update these values while maintaining foreign key integrity."

## 3. The "Blueprint" Strategy
Combine both for the ultimate context.
1. **The ZIP** provides the **Logic Flow** (How files talk to each other).
2. **The SQLite DB** provides the **Data Flow** (What values are stored and where).

By providing these, you transform the AI from a "Text Generator" into a "Senior Architect" with full visibility into your system.
