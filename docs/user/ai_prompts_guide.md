# AI Prompting Best Practices for Site Packages

The "Site Package" structure (Markdown pages, JSON forms, transparent folder layout) is designed to be easily readable by AI models. This allows you to rapidly build and upgrade your site by collaborating with an AI Assistant.

## Why this format works for AI

- **Context-Rich**: You can feed the AI specifically what it needs (e.g., "Here is my `site.json` and `pages/home.md`").
- **Isolated**: Changes to one Markdown file don't break the database.
- **Structured**: AI understands YAML Front Matter and JSON schemas perfectly.

## Best Practices

### 1. Context Setting
Always start by explaining the structure if the AI is not aware of it.
> "I am working with a GaiaAlpha Site Package. Pages are Markdown with Front Matter, and Forms are JSON schemas."

### 2. Upgrading Content
Instead of manually rewriting pages, ask the AI to do it based on a goal.

**Prompt Name**: *Refine Landing Page*
> "Here is my `pages/home.md`. Please rewrite the 'Services' section to be more punchy and focused on 'Enterprise AI'. Keep the Front Matter unchanged."

### 3. Generating Forms
Creating valid JSON schemas for forms can be tedious. Let the AI do it.

**Prompt Name**: *Create Survey Form*
> "Create a new form file `forms/customer-feedback.json` for a Customer Satisfaction Survey. It should include fields for Rating (1-5), Best Feature (text), and specific improvements (textarea)."

### 4. Styling & Theming
You can ask the AI to generate CSS based on your `assets/styles.css`.

**Prompt Name**: *Dark Mode Update*
> "Here is my `assets/styles.css`. Please provide a CSS snippet to add a Dark Mode that activates via `@media (prefers-color-scheme: dark)`."

### 5. Multi-Page Refactors
Since you have access to all files, you can ask for bulk changes.

**Prompt Name**: *SEO Optimization*
> "I will paste the content of `pages/about-us.md` and `pages/services.md`. Please suggest better `meta_description` values for each to improve SEO ranking for the keyword 'Digital Transformation'."

## Workflow Example: "Build me a Blog"

1. **Ask**: "I want to add a Blog to my site package. Please create `pages/blog.md` listing posts, and 3 example blog post pages (`pages/blog-post-1.md`, etc)."
2. **Review**: The AI generates the Markdown files.
3. **Apply**: You save them to your folder.
4. **Import**: Run `php cli.php import:site --in=./my-site`.

By treating your website as a set of flat files, you unlock the full potential of AI code generation.
