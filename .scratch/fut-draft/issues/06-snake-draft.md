# 06: Snake Draft

**What to build:** Admin starts the draft once all captains are assigned and the room is full. Draft order is randomized. Captains pick players in snake order (reversed each round). 2-minute timer per pick with auto-pick of highest-rated available player on expiry. All users see draft progress in real-time. Admin can cancel the draft and return to "waiting" state. Self-picks are automatically assigned as first picks.

**Blocked by:** 05 (Captain Assignment).

**Status:** completed

- [x] Create `draft_picks` table migration (id, room_id, team_id, player_id, pick_number, picked_by_user_id, auto_picked boolean, timestamps)
- [x] Create `DraftPick` model with relationships (belongsTo room, belongsTo team, belongsTo player, belongsTo pickedByUser)
- [x] Create `DraftController` (pick, autoPick, current)
- [x] Implement snake draft algorithm: randomized order, reversed each round
- [x] Implement 2-minute timer per pick with auto-pick fallback
- [x] Auto-pick selects highest-rated available player
- [x] Self-picks automatically assigned as first picks when draft starts
- [x] Create `DraftBoard` component (turn indicator, available players, draft history)
- [x] Real-time draft updates (all users see picks as they happen)
- [x] Admin can cancel draft (returns room to "waiting" state)
- [x] Draft ends when all team slots are filled
- [x] Feature tests: start draft, pick player (correct turn), pick player (wrong turn rejected), auto-pick on timeout, cancel draft, snake order correctness
