# 0005 — Room State Machine

## Context

Rooms progress through lifecycle states. Need clear transitions and rules for what actions are allowed in each state.

## Decision

Four states: `waiting → full → drafting → completed`. Admin can cancel draft back to `waiting`.

## State Transitions

```
waiting → full       (player_count == team_size × num_teams)
full → drafting      (admin starts draft, randomizes order)
drafting → completed (all picks made)
drafting → waiting   (admin cancels draft)
```

## Rules by State

- **waiting**: Players can be added/removed. Room settings editable. No draft actions.
- **full**: "Start Draft" button enabled for admin. Players can still be added/removed until draft starts.
- **drafting**: Draft picks active. Player list frozen (no add/remove). Timer running. Admin can cancel.
- **completed**: Teams finalized. Team colors can be chosen. Team images can be generated. Room is read-only.

## Consequences

- Strictly forward flow prevents confusion
- Admin cancel provides safety valve for mistakes
- Clear UI state: each state shows different actions and controls
- Soft deletes on rooms allow archiving without data loss
