# Chat Feature Evaluation

This document outlines the evaluation and recommended implementation plan for adding a user-to-user chat feature to the Gaia Alpha framework.

## 1. Technical Constraints & Choices
The framework currently uses:
-   **Backend**: PHP 8.x + SQLite (via PDO).
-   **Frontend**: Vue 3 (ES Modules, no build step).
-   **Server**: Standard PHP built-in server (`php -S`).

### Real-time Communication
-   **WebSockets**: Not natively supported by standard PHP setups without external services (like Pusher) or specialized runtime (like Ratchet/Swoole).
-   **Server-Sent Events (SSE)**: Possible in PHP but requires careful session handling (`session_write_close()`) to avoid locking the server, especially with the single-threaded built-in server.
-   **Polling**: The most robust solution for this specific stack. It involves the client requesting updates every X seconds.

**Recommendation**: **Short Polling** (3-5 seconds). It's simple, reliable for a lightweight app, and requires zero extra infrastructure.

## 2. Database Schema
A new table `messages` is required.

```sql
CREATE TABLE messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL,
    receiver_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(sender_id) REFERENCES users(id),
    FOREIGN KEY(receiver_id) REFERENCES users(id)
);
```

**Indexes**: sender_id, receiver_id, created_at (for sorting).

## 3. Backend Implementation
### New Model: `class/GaiaAlpha/Model/Message.php`
-   `create($from, $to, $content)`
-   `getConversation($user1, $user2, $limit = 50)`
-   `markAsRead($user1, $user2)` (optional for now)

### New Controller: `class/GaiaAlpha/Controller/ChatController.php`
-   `GET /api/chat/users`: List users with unread count (or just all users).
-   `GET /api/chat/messages/:userId`: Get messages exchanged with specific user.
-   `POST /api/chat`: Send a message.
    -   Body: `{ "to": 12, "content": "Hello" }`

## 4. Frontend Implementation
### New Component: `www/js/components/ChatPanel.js`
-   **Layout**: Split view (similar to MapPanel or specific Chat Layout).
    -   **Left**: User list (with online status if we track "last seen", otherwise just list).
    -   **Right**: Message thread + input box.
-   **Logic**:
    -   Fetch user list on mount.
    -   When user selected -> Start polling `GET /api/chat/messages/:id`.
    -   On send -> Optimistic update + `POST`.
-   **Auto-scroll**: Auto-scroll to bottom of chat window on new message.

## 5. Integration
-   Add "Chat" item to `site.js` menu (under "System" or new "Communication" group, or top level).
-   Update `site.css` for message bubbles (sent/received styles).

## 6. Effort Estimate
-   **Backend**: 2-3 hours (Model, Controller, Schema).
-   **Frontend**: 3-4 hours (UI, Polling Logic, Mobile Responsiveness).
-   **Testing**: 1 hour.
