# 02: Room Management

**What to build:** Authenticated users can create a Room with name, date, team size (5/7/11), number of teams, price, and currency (USD/EUR/COP/ARS/MXN/CLP/BRL). Users join rooms via a 6-character invite code. Admins can edit room settings, assign/revoke admin roles, and remove users. The creator is automatically assigned as Admin. Dashboard shows all rooms the user owns or has joined.

**Blocked by:** 01 (Auth & Username Support).

**Status:** completed

- [x] Create dashboard page showing user's rooms with state badges
- [x] Create room creation form (or modal on dashboard)
- [x] Create room detail page with tabs (Players, Draft, Teams, Chat)
- [x] Implement invite code generation (unique 6-char) and join flow
- [x] Implement admin role management (assign/revoke)
- [x] Feature tests: create room, update room (admin), update room (non-admin forbidden), join by code, join invalid code
