# 10: Dashboard & Room States

**What to build:** Dashboard shows all rooms the user owns or has joined with the current state (waiting/full/drafting/completed). Completed rooms are archived (hidden from main list). Room state transitions follow the state machine: waiting → full → drafting → completed (with cancel back to waiting).

**Blocked by:** 02 (Room Management), 07 (Team Finalization).

**Status:** completed

- [x] Implement room state display on dashboard (badges for waiting/full/drafting/completed)
- [x] Archive completed rooms (hidden from main dashboard list)
- [x] Implement state machine transitions in Room model
- [x] Update `RoomController@index` to filter/archive completed rooms
- [x] Feature tests: room transitions through all states, completed rooms archived on dashboard
