# Comments Plugin

The **Comments Plugin** provides a robust, polymorphic commenting and review system that can be attached to any entity in Gaia Alpha.

## Features
-   **Polymorphic**: Attach comments to any `type` and `id` pair (e.g., `lms_lesson`, `product`, `blog_post`).
-   **Threaded**: Infinite nesting of replies.
-   **Ratings**: Optional 5-star rating system (useful for Reviews).
-   **Secure**: Content sanitization and user authorization.

## Integration

### Frontend
To use the comments section in your Vue components, import the `CommentsSection` component.

```javascript
import CommentsSection from '/plugins/Comments/resources/js/CommentsSection.js';

export default {
    components: { CommentsSection },
    template: `
        <div>
            <h1>{{ product.title }}</h1>
            <!-- ... content ... -->
            
            <!-- Add Comments Section -->
            <CommentsSection 
                target-type="ecommerce_product" 
                :target-id="product.id" 
                :allow-rating="true" 
                title="Customer Reviews"
            />
        </div>
    `
}
```

### Props
| Prop | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `targetType` | String | Yes | The entity type string (e.g. `lms_lesson`) |
| `targetId` | Number/String | Yes | The ID of the entity |
| `allowRating` | Boolean | No | Enable star ratings (default: `false`) |
| `title` | String | No | Custom title (default: "Comments") |

### Backend API
The plugin exposes the following endpoints:

-   `GET /api/comments?type=...&id=...`: Get comments tree.
-   `POST /api/comments`: Add a comment. body: `{ commentable_type, commentable_id, content, parent_id, rating }`

## Database
The plugin uses the `comments` table. 
