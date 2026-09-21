# 0003 — Player as Separate Concept from User

## Context

The app has two types of people in a room: Users (who have auth accounts) and Players (draft entities with ratings). Need to clarify how these relate.

## Decision

Player is a standalone entity that belongs to a Room, not linked to any User account. A User can also be added as a Player if they're playing, but the concepts remain separate.

## Considered Options

- **Separate concepts**: Player is a draft entity (name + rating + payment). User is an auth entity. They can overlap but aren't required to
- **Users only**: Every participant must have a User account. Players are just Users with ratings
- **Hybrid**: Players can optionally link to a User account

## Consequences

- Non-registered friends can participate (just add them as Players)
- Simpler mental model: Players are "draft data," Users are "platform accounts"
- Captain assignment links a User to a team; the self-pick links a Player to that captain
- No requirement for everyone to register before a match can be organized
