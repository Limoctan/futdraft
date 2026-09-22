# 08: Chat

**What to build:** Users in a Room can send text messages in a room-wide chat. Messages display with sender name and timestamp. New messages appear in real-time.

**Blocked by:** 02 (Room Management).

**Status:** ready-for-agent

- [ ] Create `messages` table migration (id, room_id FK cascade, user_id FK, body text, timestamps)
- [ ] Create `Message` model with relationships (belongsTo room, belongsTo user)
- [ ] Create `ChatController` (index, store)
- [ ] Define PHP routes for chat under room scope
- [ ] Create `RoomChat` component (messages list + input)
- [ ] Display messages with sender name and timestamp
- [ ] Real-time message delivery
- [ ] Feature tests: send message, list messages
