# Building Site Packages with AI

Site Packages are the "Interchange Format" of Gaia Alpha. Because they consist of flat files (Markdown, JSON, PHP), they are the most effective way to collaborate with an AI to build entire websites rapidly.

## 1. AI Capabilities for Site Packages

The AI is highly proficient at the following "Site Package" tasks:

- **Structural Scaffolding**: Asking the AI to "Build a starter package for a Law Firm" results in a complete directory tree with `site.json`, `menus.json`, and thematic `pages/*.md` files.
- **Content Generation**: Drafting SEO-optimized Markdown content for dozens of pages while maintaining consistent Front Matter.
- **Form Design**: Generating complex JSON schemas for the Forms plugin, including validation rules and field types.
- **Thematic Consistency**: Ensuring that CSS in `assets/` and custom logic in `components/` stay synchronized across the entire site.
- **Import Verification**: Preparing packages that are "Import-Ready," ensuring that all media references in Markdown match the files in the `media/` folder.

## 2. Package Content & Code

A site package built by an AI typically includes:

| Component | Format | AI Role |
| :--- | :--- | :--- |
| **Manifest** | `site.json` | Defines dependencies and site metadata. |
| **Navigation** | `menus.json` | Defines hierarchy and link structures. |
| **Content** | `pages/*.md` | High-quality copy with YAML Front Matter for SEO. |
| **Logic** | `templates/*.php` | Clean, reusable PHP layouts for rendering content. |
| **UI** | `components/*.js` | Custom interactive elements (e.g., Hero sliders). |
| **Styles** | `assets/**/*.css` | Responsive design systems (often using Tailwind utilities). |

## 3. Scaling & Size Limits

The AI's ability to manage site packages scales based on the complexity:

### Small Packages (1–15 Pages)
- **Effort**: 1–2 turns.
- **AI Performance**: **Perfect**. Can generate the entire package in a single context window.

### Medium Packages (15–50 Pages)
- **Effort**: 3–5 turns.
- **AI Performance**: **High**. Requires batching the page content generation to avoid token overflow. The AI can maintain the "Global Context" (e.g., using the same brand colors across all 50 pages) effectively.

### Large Packages (50+ Pages)
- **Effort**: Multi-session.
- **AI Performance**: **Architectural**. The AI is best used to define the "Master Template" and "Page Manifest," then generating the actual page content in discrete phases.
- **Managing Assets**: Large media folders (GBs of images) should be handled as "Black Boxes." The AI manages the references (filenames/paths) but should not be asked to process the binary data.

## 4. Why Use Packages for Prompts?
Providing a ZIP of a Site Package as a prompt context is the **#1 strategy** for upgrading a site. It allows the AI to see the *entire* site architecture at once, ensuring that any new feature (like a "Blog" or "Member Area") integrates perfectly with existing styles and navigation.
