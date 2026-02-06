# Project Guidelines

## Package Manager
This project uses **pnpm** - always use pnpm commands instead of npm.

## Git Workflow
Always create a new branch off `trunk` for any work. Never commit directly to trunk. Use branch naming like `fix/issue-N-description` or `feature/description`.

## Development Environment
Use Chrome browser automation to verify changes in the WordPress editor. The dev environment may not always be running - check package.json for scripts to start it (typically `pnpm start` for watch mode, `wp-env start` for the WordPress environment).
