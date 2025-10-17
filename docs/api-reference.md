# Laravel Live Chat - API Architecture

## Directory Structure

```
src/
├── Http/
│   ├── Controllers/
│   │   └── ChatController.php          # 6 API endpoints
│   ├── Resources/
│   │   ├── MessageResource.php         # Message JSON transformation
│   │   └── ConversationResource.php    # Conversation JSON transformation
│   └── Requests/
│       └── SendMessageRequest.php      # Message validation
├── Policies/
│   └── ConversationPolicy.php          # Conversation authorization
├── Models/
│   ├── Conversation.php                # Enhanced with lastMessage()
│   └── Message.php
├── Events/
│   ├── MessageSent.php                 # Broadcasting
│   └── UserTyping.php                  # Broadcasting
└── LaravelLiveChatServiceProvider.php  # Routes & policies registration

routes/
└── api.php                              # 6 RESTful routes

config/
└── live-chat.php                        # API configuration

tests/
└── ApiTest.php                          # 20+ comprehensive tests
```

## Request Flow

```
Client Request
    ↓
Middleware (auth:sanctum)
    ↓
Route (/chat/api/conversations/{id}/messages)
    ↓
ChatController::sendMessage()
    ├─→ Form Request Validation
    ├─→ Authorization Check (Policy)
    ├─→ Create Message
    ├─→ Update Conversation
    ├─→ Broadcast Event (MessageSent)
    └─→ Return JSON Response (MessageResource)
```

## Complete API Reference

### 1. Conversation Management

#### Create/Get Conversation

```http
POST /chat/api/conversations
Content-Type: application/json
Authorization: Bearer {token}

{
  "user_id": 2
}

Response: 200 OK
{
  "data": {
    "id": 1,
    "user1_id": 1,
    "user2_id": 2,
    "other_user": {
      "id": 2,
      "name": "Bob",
      "email": "bob@example.com"
    },
    "last_message_at": "2025-10-16T10:30:00Z",
    "created_at": "2025-10-16T10:00:00Z",
    "updated_at": "2025-10-16T10:30:00Z"
  },
  "message": "Conversation retrieved successfully."
}
```

#### List Conversations

```http
GET /chat/api/conversations?page=1
Authorization: Bearer {token}

Response: 200 OK
{
  "data": [
    {
      "id": 1,
      "user1_id": 1,
      "user2_id": 2,
      "other_user": {
        "id": 2,
        "name": "Bob",
        "email": "bob@example.com"
      },
      "last_message": {
        "id": 5,
        "message": "Hello!",
        "sender_id": 2,
        "read_at": null,
        "created_at": "2025-10-16T10:30:00Z"
      },
      "unread_count": 3,
      "last_message_at": "2025-10-16T10:30:00Z",
      "created_at": "2025-10-16T10:00:00Z",
      "updated_at": "2025-10-16T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

### 2. Message Operations

#### Send Message

```http
POST /chat/api/conversations/1/messages
Content-Type: application/json
Authorization: Bearer {token}

{
  "message": "Hello, how are you?"
}

Response: 201 Created
{
  "data": {
    "id": 1,
    "conversation_id": 1,
    "sender_id": 1,
    "message": "Hello, how are you?",
    "read_at": null,
    "created_at": "2025-10-16T10:30:00Z",
    "updated_at": "2025-10-16T10:30:00Z",
    "sender": {
      "id": 1,
      "name": "Alice",
      "email": "alice@example.com"
    },
    "is_read": false
  },
  "message": "Message sent successfully."
}

Event Broadcasted: MessageSent
Channel: private-chat.1
```

#### Get Messages

```http
GET /chat/api/conversations/1/messages?page=1
Authorization: Bearer {token}

Response: 200 OK
{
  "data": [
    {
      "id": 1,
      "conversation_id": 1,
      "sender_id": 1,
      "message": "Hello!",
      "read_at": "2025-10-16T10:31:00Z",
      "created_at": "2025-10-16T10:30:00Z",
      "updated_at": "2025-10-16T10:31:00Z",
      "sender": {
        "id": 1,
        "name": "Alice",
        "email": "alice@example.com"
      },
      "is_read": true
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 50,
    "total": 1
  }
}
```

### 3. Read Receipts

#### Mark Messages as Read

```http
POST /chat/api/conversations/1/read
Authorization: Bearer {token}

Response: 200 OK
{
  "message": "Messages marked as read.",
  "updated_count": 3
}
```

### 4. Typing Indicators

#### Broadcast Typing Status

```http
POST /chat/api/conversations/1/typing
Content-Type: application/json
Authorization: Bearer {token}

{
  "is_typing": true
}

Response: 200 OK
{
  "message": "Typing status broadcasted.",
  "is_typing": true
}

Event Broadcasted: UserTyping
Channel: private-chat.1
Data: { "user_id": 1, "is_typing": true }
```

## Error Responses

### 401 Unauthorized

```json
{
    "message": "Unauthenticated."
}
```

### 403 Forbidden

```json
{
    "message": "You do not have access to this conversation."
}
```

### 422 Validation Error

```json
{
    "message": "The message field is required.",
    "errors": {
        "message": ["Please enter a message."]
    }
}
```

### 404 Not Found

```json
{
    "message": "No query results for model [Conversation]."
}
```

## Broadcasting Events

### MessageSent Event

```php
Event: MessageSent
Channel: private-chat.{conversationId}
Broadcast Name: .MessageSent
Data: {
    id: 1,
    conversation_id: 1,
    sender_id: 1,
    message: "Hello!",
    read_at: null,
    created_at: "2025-10-16T10:30:00Z",
    sender: {...}
}
```

### UserTyping Event

```php
Event: UserTyping
Channel: private-chat.{conversationId}
Broadcast Name: .user.typing
Data: {
    user_id: 1,
    is_typing: true
}
```

## Authorization

All endpoints require:

1. **Authentication:** Valid Sanctum token
2. **Authorization:** User must be part of the conversation

Authorization checked via:

-   `ConversationPolicy` registered in ServiceProvider
-   `Conversation::includesUser($user)` method
-   Returns 403 if user not authorized

## Configuration

```php
// config/live-chat.php

'routes' => [
    'enabled' => true,
    'prefix' => 'chat/api',              // Change to 'api/v1/chat' if needed
    'middleware' => ['api', 'auth:sanctum'],  // Add 'throttle:60,1' for rate limiting
],

'message' => [
    'max_length' => 5000,                 // Maximum characters per message
],

'pagination' => [
    'messages_per_page' => 50,            // Messages per page
    'conversations_per_page' => 20,       // Conversations per page
],

'broadcasting' => [
    'enabled' => true,
    'channel_prefix' => 'chat',           // Channel naming: {prefix}.{conversationId}
],
```

## Frontend Integration

### JavaScript Example with Laravel Echo

```javascript
import Echo from "laravel-echo";
import Pusher from "pusher-js";

// Initialize Echo
window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ["ws", "wss"],
});

// Listen for messages
window.Echo.private(`chat.${conversationId}`)
    .listen(".MessageSent", (e) => {
        console.log("New message:", e);
        // Add message to UI
    })
    .listen(".user.typing", (e) => {
        console.log("User typing:", e);
        // Show typing indicator
    });

// Send message
async function sendMessage(conversationId, message) {
    const response = await fetch(
        `/chat/api/conversations/${conversationId}/messages`,
        {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${token}`,
                Accept: "application/json",
            },
            body: JSON.stringify({ message }),
        }
    );

    return await response.json();
}

// Broadcast typing
function broadcastTyping(conversationId, isTyping) {
    fetch(`/chat/api/conversations/${conversationId}/typing`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ is_typing: isTyping }),
    });
}
```

## Performance Optimizations

### Database Queries

-   Eager loading: `with(['user1', 'user2', 'lastMessage', 'sender'])`
-   Indexes on: `conversation_id`, `sender_id`, `created_at`, `read_at`, `last_message_at`
-   Query optimization for unread count

### Pagination

-   Configurable per-page limits
-   Efficient offset pagination
-   Metadata included for frontend

### Broadcasting

-   `toOthers()` prevents echo to sender
-   Private channels for security
-   No database writes for typing indicators

## Testing

Run API tests:

```bash
vendor/bin/pest tests/ApiTest.php
```

Test specific endpoint:

```bash
vendor/bin/pest tests/ApiTest.php --filter=test_it_sends_a_message_successfully
```

## Next Steps

1. **Rate Limiting:** Add throttle middleware
2. **File Uploads:** Support attachments
3. **Message Search:** Full-text search
4. **Webhooks:** External integrations
5. **Analytics:** Message metrics
