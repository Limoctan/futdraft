# Issue Tracker

This project uses a **local markdown** issue tracker. Issues live as files under `.scratch/<feature>/` in this repo.

## Creating Issues

1. Create a directory under `.scratch/` named after the feature or task (e.g., `.scratch/add-dark-mode/`)
2. Create an `issue.md` file inside with a title, description, and any acceptance criteria
3. Use the `triage` skill to assign roles and move issues through the pipeline

## Directory Structure

```
.scratch/
  add-dark-mode/
    issue.md
  fix-login-bug/
    issue.md
```

## Issue Format

```markdown
# [Title]

[Description of the issue or feature request]

## Acceptance Criteria

- [ ] Criterion 1
- [ ] Criterion 2

## Status

- Role: needs-triage
```

## Notes

- No external CLI is required; all operations are file-based
- The `to-tickets`, `triage`, and `to-spec` skills read from and write to this directory
- Delete directories under `.scratch/` when issues are resolved
