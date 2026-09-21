# FUT Draft — Soccer Match Organizer

A platform for organizing soccer matches between friends: create rooms, manage player lists, draft teams via snake draft, and share results.

## Language

**Room**:
A match instance containing players, draft state, teams, and chat. Identified by a short invite code.
_Avoid_: Match, lobby, group

**Player**:
A draft entity inside a Room: name, rating (1–5), and payment status. Not a User account. Anyone in the Room can add, edit, or remove Players.
_Avoid_: Participant, member, user

**User**:
An authenticated account with email and username. Can create Rooms, join Rooms via code, and be assigned as admin or captain.
_Avoid_: Account, member

**Captain**:
A User assigned to lead a team during the draft. One per team. Picks Players from the list in snake-draft order. Captains are separate from the Player list, but one Player from the list is auto-assigned as the captain's "self" pick (first pick).
_Avoid_: Leader, team lead

**Admin**:
A User with elevated Room permissions: edit settings, assign/revoke admin, assign captains, skip payment image requirement. The Room creator is the initial Admin.
_Avoid_: Owner, creator

**Draft**:
The phase where Captains pick Players from the list in snake-draft order (random initial order, reversed each round). Begins when the Room is full (player_count == team_size × num_teams). Reserve Players can be picked after the main list is exhausted.
_Avoid_: Pick, selection

**Reserve List**:
A secondary list of Players added beyond the required team slots. Reserve Players can be picked after the main list is exhausted.
_Avoid_: Bench, extras, overflow

**Team**:
A group of Players assigned to a Captain after the Draft completes. Has a chosen color and is shareable as a generated image.
_Avoid_: Squad, group

**Payment**:
A record that a Player's fee has been paid. Requires a reference image from non-admin users. Admins can mark payment without image.
_Avoid_: Receipt, proof, transaction

**Invite Code**:
A short random alphanumeric string (6 chars) used to join a Room. No approval step required.
_Avoid_: Link, token

**Self-pick**:
The Player record assigned to a Captain as their first draft pick, representing the captain themselves. Counts toward team_size.
_Avoid_: Auto-pick, captain player
