# 0001 — Use Laravel Reverb for Real-time

## Context

The app needs real-time updates during draft picks, chat messages, player list changes, and team updates. All room participants must see changes instantly.

## Decision

Use Laravel Reverb as the WebSocket server with presence channels for room awareness.

## Considered Options

- **Laravel Reverb**: Official Laravel WebSocket server, native broadcasting integration, no third-party service
- **Pusher**: Managed service, easy setup, but external dependency and cost at scale
- **Polling**: Simpler, but not truly real-time, higher server load
- **Soketi**: Self-hosted Pusher alternative, but less native integration

## Consequences

- Reverb is first-party Laravel — guaranteed compatibility with future framework versions
- Presence channels give us "who's in the room" awareness for free
- No external service dependency — fully self-hosted
- Requires WebSocket server infrastructure (Reverb process)
