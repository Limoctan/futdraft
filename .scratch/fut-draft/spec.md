# FUT Draft — Full Platform Spec

## Status
- Role: ready-for-agent

## Problem Statement

Friends who organize casual soccer matches need a way to coordinate logistics: collecting players, splitting into fair teams, tracking who has paid, and sharing the final team assignments. Currently this is done through scattered group chats, manual team selection (which often leads to arguments), and no centralized payment tracking. The process is disorganized, unfair, and un fun.

## Solution

A web application where Users create Rooms for soccer matches, add Players with skill ratings, run a structured snake draft to form teams, track payment status, and share generated team images. The experience is real-time so all participants see updates as they happen.

## User Stories

### Authentication

1. As a User, I want to register with an email, username, and password, so that I have an identity on the platform
2. As a User, I want to log in with either my email or username, so that I have flexibility in how I authenticate
3. As a User, I want to reset my password if I forget it, so that I can regain access to my account
4. As a User, I want to enable two-factor authentication, so that my account is more secure
5. As a User, I want to register a passkey, so that I can log in without a password

### Room Management

6. As a User, I want to create a Room with a name, date, team size (5, 7, or 11), number of teams, price, and currency, so that I can organize a match
7. As a User, I want to set the currency to USD, EUR, COP, ARS, MXN, CLP, or BRL, so that I can use my local currency
8. As a User (creator), I want to be automatically assigned as Admin of the Room I create, so that I can manage it
9. As a User, I want to join a Room by entering a 6-character invite code, so that I can participate in matches my friends organize
10. As a User, I want to see all Rooms I own or have joined on my dashboard, so that I can quickly access them
11. As an Admin, I want to edit Room settings (name, date, team size, num teams, price, currency) before the draft starts, so that I can correct mistakes
12. As an Admin, I want to assign or revoke Admin role to other Users in the Room, so that management can be shared
13. As an Admin, I want to remove Users from the Room, so that I can manage participation
14. As an Admin, I want to see the invite code prominently displayed, so that I can share it with friends

### Player Management

15. As any User in a Room, I want to add a Player with a name and rating (1–5), so that the player pool is built
16. As any User in a Room, I want to edit a Player's name and rating, so that I can correct information
17. As any User in a Room, I want to remove a Player from the list, so that the pool stays accurate
18. As any User in a Room, I want to add Players beyond the required team slots (Reserve List), so that there are substitutes available
19. As the system, I want to mark a Room as "full" when player_count equals team_size multiplied by num_teams, so that the draft can begin
20. As any User in a Room, I want to see the current player count versus required slots, so that I know how many more players are needed

### Payment Tracking

21. As any User in a Room, I want to mark a Player's payment as paid by uploading a reference image, so that payment status is tracked
22. As an Admin, I want to mark a Player's payment as paid without requiring an image, so that I can confirm cash or in-person payments
23. As any User in a Room, I want to see which Players have paid and which haven't, so that I can follow up
24. As any User in a Room, I want to see the reference image for a Player's payment, so that I can verify proof of payment

### Captain Assignment

25. As an Admin, I want to assign a Captain (a User in the Room) to each team, so that the draft has leaders
26. As an Admin, I want to assign myself as a Captain, so that I can participate in the draft
27. As an Admin, I want to assign a Player from the list to each Captain as their "self-pick" (first pick, counts toward team_size), so that captains are part of their own team
28. As an Admin, I want to start the draft once all captains are assigned and the room is full, so that team formation begins

### Draft (Snake Draft)

29. As the system, I want to randomize the initial draft order when the draft starts, so that the selection is fair
30. As the system, I want to reverse the pick order each round (snake draft), so that every Captain gets equitable picks
31. As a Captain, I want to see whose turn it is and which Players are available, so that I can make my pick
32. As a Captain, I want to pick a Player from the available list on my turn, so that I build my team
33. As the system, I want to enforce a 2-minute timer per pick, so that the draft doesn't stall
34. As the system, I want to auto-pick the highest-rated available Player if the timer expires, so that the draft continues
35. As any User in a Room, I want to see the draft progress in real-time (who picked whom, current turn), so that everyone stays informed
36. As the system, I want to end the draft when all team slots are filled, so that teams are finalized
37. As an Admin, I want to cancel the draft and return the Room to "waiting" state, so that mistakes can be corrected before finalization
38. As a Captain, I want my self-pick to be automatically assigned as my first pick when the draft starts, so that I don't need to pick myself manually

### Team Finalization

39. As a Captain, I want to choose a team color from a predefined palette of ~12 colors after the draft completes, so that my team is visually distinct
40. As the system, I want to prevent duplicate team colors within a Room, so that every team is distinguishable
41. As any User in a Room, I want to see the finalized teams with their captain, players, and color, so that I know the results
42. As a Captain, I want to generate a shareable image of my team (player names, captain, color), so that I can post it in group chats or social media
43. As a Captain, I want the team image to be generated client-side (html2canvas), so that I can preview and download it instantly

### Reserve List

44. As the system, I want to make Reserve List players available for picking only after the main player list is exhausted during the draft, so that the primary draft stays focused
45. As a Captain, I want to pick from the Reserve List after the main list is empty, so that I can fill remaining slots if needed

### Chat

46. As any User in a Room, I want to send text messages in a room-wide chat, so that I can communicate with other participants
47. As any User in a Room, I want to see chat messages with sender name and timestamp, so that I can follow the conversation
48. As any User in a Room, I want to see new chat messages in real-time, so that the conversation flows naturally

### Real-time Updates

49. As any User in a Room, I want to see player list changes (add/edit/remove) reflected immediately, so that everyone works with current data
50. As any User in a Room, I want to see draft picks reflected immediately, so that the draft feels live
51. As any User in a Room, I want to see team color/name changes reflected immediately, so that finalization is visible to all
52. As any User in a Room, I want to see payment status changes reflected immediately, so that payment tracking is current

### Room Lifecycle

53. As the system, I want to transition a Room through states: waiting → full → drafting → completed, so that the match lifecycle is clear
54. As the system, I want to allow an Admin to cancel a draft and return to "waiting" state, so that errors can be corrected
55. As a User, I want to see the current state of each Room on my dashboard, so that I know what action is needed
56. As a User, I want past completed Rooms to be archived (hidden from main list), so that my dashboard stays clean

## Implementation Decisions

### Database Schema

**9 migrations** covering:

- `users` table: add `username` column (string, unique, indexed)
- `rooms` table: id, user_id (creator), name, date, invite_code (unique), team_size (tinyint), num_teams (tinyint), price_in_cents (unsigned bigint), currency (string, default 'USD'), status (string enum), draft_order (json, nullable), current_pick_index (int), draft_started_at (timestamp, nullable), timestamps, soft_deletes
- `players` table: id, room_id (FK, cascade), name, rating (tinyint 1-5), is_captain (boolean), captain_user_id (FK, nullable), timestamps
- `teams` table: id, room_id (FK, cascade), captain_user_id (FK), name (nullable), color (string), pick_order (int), timestamps
- `room_users` pivot: room_id, user_id, is_admin (boolean), unique constraint on [room_id, user_id]
- `team_players` pivot: team_id, player_id, pick_number (int)
- `draft_picks` table: id, room_id, team_id, player_id, pick_number, picked_by_user_id, auto_picked (boolean), timestamps
- `messages` table: id, room_id (FK, cascade), user_id (FK), body (text), timestamps
- `payments` table: id, player_id (FK, cascade), marked_by_user_id (FK), reference_image_path (nullable), paid_at (timestamp), timestamps

### Enums

- `RoomStatus`: Waiting, Full, Drafting, Completed (string backed)
- `Currency`: USD, EUR, COP, ARS, MXN, CLP, BRL (string backed, with label/format methods per currency)

### Models and Relationships

- `User`: hasMany rooms (owned), belongsToMany rooms (joined via room_users), hasMany messages, hasMany teams (as captain), hasMany payments (marked)
- `Room`: belongsTo creator (User), belongsToMany users (with is_admin pivot), hasMany players, hasMany teams, hasMany messages, hasMany draftPicks
- `Player`: belongsTo room, belongsTo captainUser (nullable), belongsToMany teams (via team_players), hasOne payment
- `Team`: belongsTo room, belongsTo captain (User), belongsToMany players (via team_players)
- `Message`: belongsTo room, belongsTo user
- `DraftPick`: belongsTo room, belongsTo team, belongsTo player, belongsTo pickedByUser
- `Payment`: belongsTo player, belongsTo markedByUser

### Authentication

- Fortify configured with `'username' => 'email'` (default field)
- Custom `Fortify::authenticateUsing()` closure to allow login with either email OR username
- `CreateNewUser` action updated to require and store `username`
- `ProfileValidationRules` updated with username uniqueness validation

### Broadcasting (Laravel Reverb)

- Laravel Reverb installed via `composer require laravel/reverb` and `php artisan reverb:install`
- Frontend: `laravel-echo` + `pusher-js` installed, configured via `@laravel/echo-react`'s `configureEcho()`
- Room presence channel: `room.{room_id}` — authorized via `routes/channels.php`
- Events broadcast: RoomUpdated, PlayerAdded/Updated/Removed, DraftPickMade, DraftStarted, DraftCompleted, NewMessage, TeamUpdated, CaptainAssigned, PaymentMarked

### Draft Algorithm (Snake Draft)

```
draft_order = randomized array of team IDs
current_pick_index tracks position

current_team = draft_order[current_pick_index % num_teams]
direction reverses each round:
  round = floor(current_pick_index / num_teams)
  if round is odd: reverse the order within that round

Self-pick: admin assigns a Player to each Captain before draft starts.
That Player becomes the Captain's first pick (counts toward team_size).
```

### Room State Machine

```
waiting → full      (when player_count == team_size × num_teams)
full → drafting     (admin starts draft, randomizes order)
drafting → completed (all picks made)
drafting → waiting   (admin cancels draft)
```

### Controller Architecture

- `RoomController`: index, store, show, update, join, cancelDraft, assignCaptains, startDraft
- `PlayerController`: store, update, destroy, markPaid
- `DraftController`: pick, autoPick, current
- `ChatController`: index, store
- `TeamController`: update

### Route Structure

All routes under `auth` + `verified` middleware. Room-scoped routes use route model binding with `{room}`. Key routes:

- `GET /dashboard` → RoomController@index
- `POST /rooms` → RoomController@store
- `GET /rooms/{room}` → RoomController@show
- `PATCH /rooms/{room}` → RoomController@update
- `POST /rooms/join` → RoomController@join
- `POST /rooms/{room}/start-draft` → RoomController@startDraft
- `POST /rooms/{room}/cancel-draft` → RoomController@cancelDraft
- `POST /rooms/{room}/assign-captains` → RoomController@assignCaptains
- `POST /rooms/{room}/players` → PlayerController@store
- `PATCH /rooms/{room}/players/{player}` → PlayerController@update
- `DELETE /rooms/{room}/players/{player}` → PlayerController@destroy
- `POST /rooms/{room}/players/{player}/pay` → PlayerController@markPaid
- `POST /rooms/{room}/draft/pick` → DraftController@pick
- `GET /rooms/{room}/draft/current` → DraftController@current
- `GET /rooms/{room}/messages` → ChatController@index
- `POST /rooms/{room}/messages` → ChatController@store
- `PATCH /rooms/{room}/teams/{team}` → TeamController@update

### Frontend Architecture

**Pages:**
- `dashboard.tsx` — updated: list rooms, join by code, create room
- `rooms/show.tsx` — new: main room page with tabs (Players, Draft, Teams, Chat)
- `rooms/create.tsx` — new: create room form (or modal on dashboard)

**Components:**
- `PlayerList` — table with add/edit/remove/pay actions
- `DraftBoard` — live draft view with turn indicator, available players, draft history
- `TeamView` — team cards with color picker
- `RoomChat` — chat messages + input
- `RoomHeader` — room info, invite code, admin controls
- `TeamImageCard` — html2canvas target for shareable image generation

**Real-time:**
- `useEchoPresence` hook from `@laravel/echo-react` in room page
- Listens on `room.{id}` presence channel
- Updates UI via Inertia `router.reload()` or local state mutations

### Image Generation

- Client-side using `html2canvas` library
- `TeamImageCard` component renders team name, color, captain, and player list
- User clicks "Share" → html2canvas captures the card → download as PNG
- No server-side image generation needed

### Currency Handling

- Stored as integer cents in `price_in_cents` column
- Display formatted per currency: USD ($10.00), EUR (€10,00), COP (COL$10.000), ARS (ARS$10.000), MXN ($10.00), CLP (CLP$10.000), BRL (R$10,00)
- Enum provides `format()` method that returns localized string

### Payment Image Storage

- Stored in `storage/app/payments/{room_id}/`
- Filenames: `{player_id}_{timestamp}.{ext}`
- Validated as image MIME types on upload
- Admins can call markPaid endpoint without image (nullable path)

## Testing Decisions

### Test Philosophy

- Test external behavior (HTTP responses, database state, broadcast events) not implementation details
- Use Pest with `->actingAs()` for authenticated tests
- Use factories for all models
- Feature tests preferred over unit tests for controllers and workflows

### Modules to Test

1. **Auth**: Registration with username, login with email, login with username, login fails with wrong credentials
2. **Room CRUD**: Create room, update room (admin), update room (non-admin forbidden), join by code, join invalid code
3. **Player Management**: Add player, edit player, remove player, mark paid with image, mark paid as admin without image
4. **Draft Flow**: Start draft (triggers randomization), pick player (correct turn), pick player (wrong turn rejected), auto-pick on timeout, cancel draft
5. **Team Management**: Update team color, prevent duplicate colors
6. **Chat**: Send message, list messages
7. **Broadcasting**: Events broadcast on correct channels, channel authorization works

### Prior Art

- Existing Pest tests in `tests/` directory (if any) follow standard Laravel feature test patterns
- Fortify already has test infrastructure for auth flows

### Key Test Scenarios

- Room transitions: waiting → full → drafting → completed
- Draft snake order correctness (verify pick order matches expected sequence)
- Auto-pick selects highest-rated available player
- Admin can bypass payment image requirement
- Non-admin cannot bypass payment image requirement
- Invite code generation is unique
- Channel authorization: room member can subscribe, non-member cannot

## Out of Scope

- Mobile app (web only for now)
- Payment processing (just tracking, no actual money movement)
- Team editing after draft completion (only color/name changes)
- Player reordering or manual draft pick override
- Multiple drafts per room
- Room templates or recurring matches
- Email notifications (real-time only)
- Admin dashboard or analytics
- User profiles beyond username/email
- Player ratings persistence across rooms (each room has independent players)
- Undo/redo during draft
- Spectator mode (only Users in the room can view)

## Further Notes

- The app is built with Laravel 13 + Inertia + React 19 + Tailwind CSS v4
- Fortify handles auth; Wayfinder generates typed route helpers
- Reverb is the official Laravel WebSocket server — no third-party service dependency
- The snake draft algorithm is the same used in fantasy sports — well-understood and fair
- html2canvas for image generation avoids server load and gives instant preview
- All real-time updates use Laravel broadcasting with presence channels (awareness of who's in the room)
