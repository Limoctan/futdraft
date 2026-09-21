# 0004 — Client-side Team Image Generation

## Context

After the draft completes, captains want to share their team as a visual image (player names, team color, captain).

## Decision

Use html2canvas to render a React component (TeamImageCard) to a canvas, then download as PNG. No server-side image generation.

## Considered Options

- **Client-side (html2canvas)**: Renders HTML/CSS to canvas. Instant preview, no server load, user controls when to download
- **Server-side (spatie/image)**: Generates PNG on server. Requires image processing library, server resources, and download endpoint
- **Server-side (puppeteer)**: Headless browser screenshot. Heavy dependency, overkill for this use case

## Consequences

- Zero server load for image generation
- Instant preview — user sees the image before downloading
- html2canvas is well-maintained and widely used
- Image quality depends on browser rendering (good enough for social sharing)
- No need to store generated images on server
