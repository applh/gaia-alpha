# Comments Plugin Proposal

## 1. Overview
The **Comments Plugin** serves as a centralized, generic system for handling user feedback, discussions, and reviews across the entire Gaia Alpha ecosystem. Instead of implementing comment logic repeatedly in LMS, E-commerce, and CMS plugins, this plugin provides a reusable "engine" that can be attached to any content type.

## 2. Goals
- **Universality**: Attach comments to any entity (Course, Lesson, Product, Blog Post) using polymorphic relationships.
- **Features**: Threading (nested replies), Markdown support, Moderation queues (Pending/Approved).
- **Reviews**: Support for star ratings (1-5) to function as a review system for generic items (e.g., E-commerce products).
- **Integration**: Simple frontend component drop-in `<Comments target-type="..." target-id="..." />`.

## 3. Database Schema
The plugin uses a flexible schema to store comments and their metadata.

```sql
-- plugins/Comments/schema.sql

CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,              -- NULL accounts for guest comments (if enabled)
    
    -- Polymorphic Relation
    commentable_type VARCHAR(100) NOT NULL, -- e.g., 'lms_lesson', 'product', 'blog_post'
    commentable_id INTEGER NOT NULL,
    
    parent_id INTEGER NULL,            -- For nested replies
    
    content TEXT NOT NULL,
    rating INTEGER NULL,               -- 1-5 for reviews, NULL for standard comments
    
    status VARCHAR(20) DEFAULT 'approved', -- approved, pending, spam, trash
    
    author_name VARCHAR(100) NULL,     -- Cached name for display or for guests
    author_email VARCHAR(100) NULL,    -- For guests
    
    meta_data TEXT NULL,               -- JSON for extra fields (verified purchase, etc.)
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY(parent_id) REFERENCES comments(id) ON DELETE CASCADE
);

CREATE INDEX idx_comments_lookup ON comments(commentable_type, commentable_id, status);
```

## 4. Architecture

### 4.1 Namespace & Structure
Namespace: `Comments`
Path: `plugins/Comments/`

- `plugin.json`: Definitions.
- `index.php`: Initialization and API route registration.
- `class/Comment.php`: Model.
- `class/CommentService.php`: Business logic (create, reply, moderate).
- `class/CommentsController.php`: API implementation.

### 4.2 Integration Flow

#### Backend (API)
The plugin exposes endpoints used by the frontend component:
- `GET /api/comments?type=lms_lesson&id=10`: Fetch comments for a specific item.
- `POST /api/comments`: Submit a new comment.
- `PUT /api/comments/:id`: Edit (if allowed).
- `DELETE /api/comments/:id`: Delete (if allowed).

#### Frontend (Vue Component)
A global Vue component `CommentsSection.vue` will be registered. Plugins can simply use this tag in their templates.

```html
<!-- Example in LMS LessonPlayer.vue -->
<div class="lesson-discussion">
    <h3>Q&A</h3>
    <CommentsSection 
        type="lms_lesson" 
        :id="currentLesson.id" 
        :allow-rating="false" 
    />
</div>
```

```html
<!-- Example in E-commerce ProductDetail.vue -->
<div class="product-reviews">
    <h3>Customer Reviews</h3>
    <CommentsSection 
        type="ecommerce_product" 
        :id="product.id" 
        :allow-rating="true" 
        title="Reviews"
    />
</div>
```

## 5. Implementation Strategy

### Phase 1: Core Plugin
1.  Create `plugins/Comments/` directory.
2.  Implement `schema.sql` and `plugin.json`.
3.  Create `Comment` model and `CommentsController`.
4.  Implement basic CRUD API.

### Phase 2: Frontend Component
1.  Develop `CommentsSection.vue`.
    -   List view (threaded).
    -   Input form (Guest vs Logged in).
    -   Rating stars selector (if enabled).

### Phase 3: Integration
1.  **LMS**: Add `<CommentsSection>` to the Lesson view.
2.  **E-commerce**: Add `<CommentsSection>` to the Product view.
3.  **Admin**: Create a "Comments" management page in the Dashboard to moderate pending comments.

## 6. Security & Moderation
-   **Anti-Spam**: Rate limiting on API endpoints.
-   **Sanitization**: All HTML is stripped from comments; Markdown is rendered safely on the client or sanitized server-side.
-   **Permissions**: 
    -   Users can only edit/delete their own comments (within a time window).
    -   Admins can manage all logic.
