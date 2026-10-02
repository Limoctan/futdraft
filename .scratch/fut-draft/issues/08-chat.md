# 08: Chat

**What to build:** Users in a Room can send text messages in a room-wide chat. Messages display with sender name and timestamp. New messages appear in real-time.

**Blocked by:** 02 (Room Management).

**Status:** completed

- [x] Create `messages` table migration (id, room_id FK cascade, user_id FK, body text, timestamps)
- [x] Create `Message` model with relationships (belongsTo room, belongsTo user)
- [x] Create `ChatController` (index, store)
- [x] Define PHP routes for chat under room scope
- [x] Create `RoomChat` component (messages list + input)
- [x] Display messages with sender name and timestamp
- [x] Real-time message delivery
- [x] Feature tests: send message, list messages
