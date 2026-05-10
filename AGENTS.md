# Project Guidelines

## Package Manager
This project uses **pnpm** - always use pnpm commands instead of npm.

## Git Workflow
Always create a new branch off `trunk` for any work. Never commit directly to trunk. Use branch naming like `fix/issue-N-description` or `feature/description`.

## Development Environment
Use browser automation to verify changes in the WordPress editor when relevant.
The dev environment may not always be running - use package scripts:

- `pnpm start` for asset watch mode
- `pnpm env:start` for the WordPress environment
- `pnpm test:e2e` for Playwright end-to-end tests
