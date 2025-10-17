# Changelog

All notable changes to `laravel-live-chat` will be documented in this file.

## 0.0.1 - 2025-10-17

Initial release of Laravel Live Chat package.

### Added
- 1-to-1 real-time chat functionality using Laravel Reverb
- Message and Conversation models with relationships
- Broadcasting events (MessageSent, UserTyping)
- Conversation and Message controllers with RESTful API
- Channel authorization for secure private chat channels
- Typing indicator support
- Message cleanup Artisan command
- Installation command for publishing assets
- Factories for testing (Conversation, Message)
- Comprehensive test suite with Pest
- Frontend JavaScript client helpers
- Blade components for chat UI
- API resource classes for consistent responses
