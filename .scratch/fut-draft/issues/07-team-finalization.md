# 07: Team Finalization

**What to build:** After the draft completes, Captains choose a team color from a predefined palette of ~12 colors. Duplicate team colors within a Room are prevented. Captains can generate a shareable image of their team (player names, captain, color) using html2canvas for instant preview and download.

**Blocked by:** 06 (Snake Draft).

**Status:** completed

- [x] Create `TeamController` (update) for color/name changes
- [x] Create color palette (~12 colors) with duplicate prevention per room
- [x] Create `TeamView` component (team cards with color picker)
- [x] Create `TeamImageCard` component (html2canvas target)
- [x] Implement client-side image generation (html2canvas) for shareable team image
- [x] Display finalized teams with captain, players, and color
- [x] Feature tests: update team color, prevent duplicate colors
