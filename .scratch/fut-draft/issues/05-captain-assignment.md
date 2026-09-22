# 05: Captain Assignment

**What to build:** Admin assigns a Captain (a User in the Room) to each team. Admin assigns a Player from the list to each Captain as their "self-pick" (first pick, counts toward team_size). Admin can assign themselves as a Captain. Teams are created during this step.

**Blocked by:** 03 (Player Management), 02 (Room Management).

**Status:** ready-for-agent

- [ ] Create `teams` table migration (id, room_id FK cascade, captain_user_id FK, name nullable, color string, pick_order int, timestamps)
- [ ] Create `team_players` pivot table (team_id, player_id, pick_number int)
- [ ] Create `Team` model with relationships (belongsTo room, belongsTo captain User, belongsToMany players via team_players)
- [ ] Add `assignCaptains` action to `RoomController`
- [ ] Implement captain assignment UI (select users for each team)
- [ ] Implement self-pick assignment (admin assigns a player to each captain)
- [ ] Validate: all captains must be assigned before draft can start
- [ ] Feature tests: assign captains, assign self-picks, cannot start draft without all captains
