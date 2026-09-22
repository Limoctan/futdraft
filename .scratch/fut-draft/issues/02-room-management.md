# 02: Room Management

**What to build:** Authenticated users can create a Room with name, date, team size (5/7/11), number of teams, price, and currency (USD/EUR/COP/ARS/MXN/CLP/BRL). Users join rooms via a 6-character invite code. Admins can edit room settings, assign/revoke admin roles, and remove users. The creator is automatically assigned as Admin. Dashboard shows all rooms the user owns or has joined.

**Blocked by:** 01 (Auth & Username Support).

**Status:** ready-for-agent

- [ ] Create `rooms` table migration (id, user_id, name, date, invite_code, team_size, num_teams, price_in_cents, currency, status, draft_order, current_pick_index, draft_started_at, timestamps, soft_deletes)
- [ ] Create `room_users` pivot table (room_id, user_id, is_admin, unique constraint)
- [ ] Create `Room` model with relationships (belongsTo creator, belongsToMany users with is_admin pivot, hasMany players, hasMany teams)
- [ ] Create `Currency` enum (USD, EUR, COP, ARS, MXN, CLP, BRL) with label/format methods
- [ ] Create `RoomStatus` enum (Waiting, Full, Drafting, Completed)
- [ ] Create `RoomController` (index, store, show, update, join)
- [ ] Define PHP routes matching Wayfinder generated routes
- [ ] Create dashboard page showing user's rooms with state badges
- [ ] Create room creation form (or modal on dashboard)
- [ ] Create room detail page with tabs (Players, Draft, Teams, Chat)
- [ ] Implement invite code generation (unique 6-char) and join flow
- [ ] Implement admin role management (assign/revoke)
- [ ] Feature tests: create room, update room (admin), update room (non-admin forbidden), join by code, join invalid code
