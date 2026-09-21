# Domain Documentation

This project uses a **single-context** layout.

## Context

- `CONTEXT.md` at the repo root contains the primary domain context
- `docs/adr/` at the repo root contains Architecture Decision Records

## Rules

- Read `CONTEXT.md` before making architectural changes
- Consult `docs/adr/` when making decisions that affect the project's structure
- If no `CONTEXT.md` exists yet, create one when the project's domain becomes clear

## Creating ADRs

When making significant architectural decisions, create an ADR in `docs/adr/`:

```bash
mkdir -p docs/adr
```

Name files with a sequential number and short title: `001-use-inertia-for-spa.md`
