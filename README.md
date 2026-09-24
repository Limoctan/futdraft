# FutDraft

A Web app for organizing football matches between friends. Players create or join rooms, assign captains, take turns drafting players, and track payments — built with Laravel and Inertia (React).

## Stack

- PHP 8.5 / Laravel 13
- Inertia v3 + React 19 + TypeScript
- Tailwind CSS v4
- Postgres
- Laravel Fortify (auth, 2FA, passkeys)
- Pest, Larastan, Pint

## Setup

```bash
composer run setup
```

This installs dependencies, creates `.env`, generates an app key, runs migrations, and builds the frontend.

## Development

```bash
composer run dev
```

Runs the Laravel dev server, Vite, and related tooling together.

## Testing & checks

```bash
composer run test        # Pint check + PHPStan + Pest
composer run ci:check    # frontend checks + tests
npm run check            # frontend lint/format
npm run types:check      # TypeScript
```

## Key features

- Rooms with join codes and role-based access (admin, players)
- Turn-based drafting with manual and auto picks
- Player management and payment tracking
- Auth: email/password, 2FA, passkeys
