# 06: Snake Draft

**What to build:** Admin starts the draft once all captains are assigned and the room is full. Draft order is randomized. Captains pick players in snake order (reversed each round). 2-minute timer per pick with auto-pick of highest-rated available player on expiry. All users see draft progress in real-time. Admin can cancel the draft and return to "waiting" state. Self-picks are automatically assigned as first picks.

**Blocked by:** 05 (Captain Assignment).

**Status:** ready-for-agent

- [ ] Create `draft_picks` table migration (id, room_id, team_id, player_id, pick_number, picked_by_user_id, auto_picked boolean, timestamps)
- [ ] Create `DraftPick` model with relationships (belongsTo room, belongsTo team, belongsTo player, belongsTo pickedByUser)
- [ ] Create `DraftController` (pick, autoPick, current)
- [ ] Implement snake draft algorithm: randomized order, reversed each round
- [ ] Implement 2-minute timer per pick with auto-pick fallback
- [ ] Auto-pick selects highest-rated available player
- [ ] Self-picks automatically assigned as first picks when draft starts
- [ ] Create `DraftBoard` component (turn indicator, available players, draft history)
- [ ] Real-time draft updates (all users see picks as they happen)
- [ ] Admin can cancel draft (returns room to "waiting" state)
- [ ] Draft ends when all team slots are filled
- [ ] Feature tests: start draft, pick player (correct turn), pick player (wrong turn rejected), auto-pick on timeout, cancel draft, snake order correctness
