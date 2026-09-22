# 03: Player Management

**What to build:** Users in a Room can add Players with a name and rating (1–5), edit player details, and remove players. The player count vs required slots is displayed. The Room automatically transitions to "full" when player_count equals team_size × num_teams. Reserve List players can be added beyond the required slots.

**Blocked by:** 02 (Room Management).

**Status:** completed

- [x] Create `players` table migration (id, room_id FK cascade, name, rating tinyint 1-5, is_captain boolean, captain_user_id FK nullable, timestamps)
- [x] Create `Player` model with relationships (belongsTo room, belongsTo captainUser nullable, belongsToMany teams via team_players, hasOne payment)
- [x] Create `PlayerController` (store, update, destroy)
- [x] Define PHP routes for player CRUD under room scope
- [x] Create `PlayerList` component (table with add/edit/remove actions)
- [x] Display current player count vs required slots
- [x] Implement room status transition: waiting → full when player_count == team_size × num_teams
- [x] Allow adding players beyond required slots (Reserve List)
- [x] Feature tests: add player, edit player, remove player, room transitions to full at capacity
