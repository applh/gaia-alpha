# Example: Corporate Enterprise Website

This document provides a reference example of a "Site Package" for a typical B2B Enterprise website consisting of approximately 10 pages. You can structure your folders like this to create a robust Starter Kit.

## Folder Structure

```
/
  assets/
    logo.svg
    styles.css
  components/
    hero-banner.js
    pricing-table.js
  forms/
    contact-sales.json
    newsletter.json
  media/
    office-hq.webp
    team-photo.webp
    product-demo.webp
  pages/
    home.md
    about-us.md
    services.md
    services-consulting.md
    services-implementation.md
    products.md
    case-studies.md
    careers.md
    blog.md
    contact.md
  templates/
    landing.php
    standard.php
  site.json
```

## Page List (10 Pages)

Here is a breakdown of the typical pages and their Front Matter configuration.

| Page Title | Slug | Template | Description |
| :--- | :--- | :--- | :--- |
| **Home** | `home` (or `/`) | `landing` | Main entry point. High-level value prop. |
| **About Us** | `about-us` | `standard` | Company history, mission, and team. |
| **Services** | `services` | `standard` | Overview of service offerings. |
| **- Consulting** | `services-consulting` | `standard` | Detailed consulting capabilities. |
| **- Implementation** | `services-implementation` | `standard` | Technical implementation details. |
| **Products** | `products` | `standard` | Product features and benefits. |
| **Case Studies** | `case-studies` | `standard` | Customer success stories. |
| **Careers** | `careers` | `standard` | Job openings and culture. |
| **Blog** | `blog` | `standard` | Company news and articles. |
| **Contact** | `contact` | `landing` | Contact form & office locations. |

## Example Files

### 1. `site.json`
```json
{
  "name": "Acme Corp Enterprise Site",
  "version": "2.0.0",
  "plugins": ["contact-form", "seo-pack", "analytics"],
  "config": {
    "theme": "enterprise-blue",
    "homepage": "home"
  }
}
```

### 2. `pages/home.md`
```markdown
---
title: "Home"
slug: "home"
template_slug: "landing"
meta_description: "Acme Corp provides world-class enterprise solutions."
---

# Building the Future of Enterprise

![Office Headquarters](./media/office-hq.webp)

We help Fortune 500 companies scale.

## Our Services
...
```

### 3. `forms/contact-sales.json`
```json
{
  "title": "Contact Sales",
  "slug": "contact-sales",
  "submit_label": "Request Demo",
  "schema": [
    {
      "type": "textfield",
      "key": "companyName",
      "label": "Company Name",
      "required": true
    },
    {
      "type": "email",
      "key": "email",
      "label": "Work Email",
      "required": true
    },
    {
      "type": "textarea",
      "key": "needs",
      "label": "Project Needs"
    }
  ]
}
```

## Usage
To use this structure:
1. Create the folders and files as shown above.
2. Run `php cli.php import:site --in=./docs/examples/enterprise_site`
