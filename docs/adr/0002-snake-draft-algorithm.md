# 0002 — Snake Draft Algorithm

## Context

The app needs a fair draft mechanism for splitting players into teams. Captains take turns picking players from a shared pool.

## Decision

Use a snake draft with randomized initial order. The pick order reverses each round (e.g., Round 1: A,B,C; Round 2: C,B,A; Round 3: A,B,C).

## Considered Options

- **Snake draft**: Standard fantasy sports approach. Fair because the initial random order determines advantage, but the snake reversal compensates
- **Linear draft**: Same order every round. Simple but unfair — first picker always gets first pick
- **Auction draft**: Each captain has a budget, bids on players. More complex, harder to implement
- **Random assignment**: System randomly assigns players to teams. No captain involvement, less engaging

## Consequences

- Well-understood algorithm — users familiar with fantasy sports will recognize it
- Fair distribution: early picks in round 1 are compensated by late picks in round 2
- Initial randomization ensures no captain has permanent advantage
- 2-minute timer with auto-pick (highest-rated) prevents stalling
