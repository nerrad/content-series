# Content Series

A modern, block-editor-native WordPress plugin for managing content series. Group posts together into series with full Gutenberg integration.

> [!Note]
> This is mostly for personal use right now as I'm using this partially as an experiment to see how far I can get with just vibe-coding (for the most part - still need to review/fix!).

## Features

- **Block Editor Native**: Built specifically for the WordPress block editor (Gutenberg)
- **Series Taxonomy**: Organize posts into series using a custom taxonomy
- **Custom Blocks**: Two powerful blocks for displaying series content:
  - **Series Navigation**: Display previous/next navigation within a series
  - **Series Post List**: Show a list of all posts in the current series
- **Editor Integration**: Sidebar fields for series order and optional short titles
- **Quick Edit Support**: Edit series part numbers directly from the posts list table
- **Catalog Variation**: A `core/terms-query` variation for displaying all series
- **Block Bindings**: Custom term-meta binding source for series metadata (e.g. icon)
- **Archive Enhancements**: Adds contextual series information on non-series archive views
- **Legacy Compatibility**: Compatible with the PublishPress Series plugin (uses the same taxonomy slug)
- **REST API Support**: Full REST API integration for headless WordPress setups
- **Block Templates**: Archive and catalog block templates for series display

## Requirements

- WordPress 6.9 or higher
- PHP 8.1 or higher
- Node.js and pnpm (for development)

## Installation

1. Download or clone this repository into your WordPress plugins directory:

   ```bash
   cd wp-content/plugins
   git clone https://github.com/nerrad/content-series.git
   ```

2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Install Node.js dependencies:

   ```bash
   pnpm install
   ```

4. Build the plugin assets:

   ```bash
   pnpm build
   ```

5. Activate the plugin through the WordPress admin panel.

## Development

### Prerequisites

- [Node.js](https://nodejs.org/) (with pnpm)
- [Composer](https://getcomposer.org/)
- [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) (included as dev dependency)

### Setup

1. Clone the repository:

   ```bash
   git clone https://github.com/nerrad/content-series.git
   cd content-series
   ```

2. Install dependencies:

   ```bash
   composer install
   pnpm install
   ```

3. Start the development environment:

   ```bash
   pnpm env:start
   ```

   This will set up a local WordPress instance using wp-env.

4. Build the plugin:

   ```bash
   pnpm build
   ```

   Or run in watch mode for development:

   ```bash
   pnpm start
   ```

### Available Scripts

#### Build & Development

- `pnpm build` - Build production assets
- `pnpm start` - Start development mode with watch
- `pnpm check-types` - Run TypeScript type checking

#### Linting & Formatting

- `pnpm lint:js` - Lint JavaScript/TypeScript files
- `pnpm lint:css` - Lint CSS/SCSS files
- `pnpm lint:pkg-json` - Lint `package.json`
- `pnpm lint:php` - Lint PHP files using PHPCS
- `pnpm lint:php:fix` - Auto-fix PHP linting issues
- `pnpm format` - Format code using wp-scripts
- `pnpm packages-update` - Update WordPress packages managed by `wp-scripts`

#### Testing

- `pnpm test` - Run PHPUnit tests
- `pnpm test:watch` - Run PHPUnit tests in watch mode
- `pnpm test:js` - Run JavaScript unit tests (Jest)
- `pnpm test:js:watch` - Run JavaScript unit tests in watch mode
- `pnpm test:js:coverage` - Generate JavaScript unit test coverage
- `pnpm test:e2e` - Run Playwright end-to-end tests
- `pnpm test:e2e:ui` - Run Playwright tests in UI mode
- `pnpm test:e2e:debug` - Run Playwright tests in debug mode

#### Environment Management

- `pnpm env:start` - Start wp-env environment
- `pnpm env:stop` - Stop wp-env environment
- `pnpm env:clean` - Clean wp-env environment
- `pnpm env:destroy` - Destroy wp-env environment

### Git Worktrees

This plugin is configured to use a shared wp-env environment across multiple git worktrees. This allows you to:

- Share a single WordPress installation and database across all worktrees
- Test different plugin variations without stopping/starting wp-env
- Maintain consistent test data across worktree switches

**How it works:**

The `env:*` scripts use a wrapper script (`bin/scripts/wp-env-wrapper.sh`) that:

1. Creates a shared environment directory at `~/.wp-env-shared/content-series/`
2. Creates a symlink in that directory pointing to your current worktree
3. Configures wp-env to use the shared environment with the symlinked plugin

**Usage:**

Simply use the standard `env:*` commands from any worktree:

```bash
# From any worktree
pnpm env:start
```

The wrapper script automatically sets up the shared environment and points it to your current worktree. When you switch worktrees, the symlink is automatically updated to point to the new worktree on the next `env:*` command.

**Benefits:**

- Single WordPress download and installation
- Shared database for consistent testing
- No need to stop/start wp-env when switching worktrees
- Automatic symlink management

## Usage

### Creating a Series

1. Go to **Posts > Series** in the WordPress admin
2. Create a new series term
3. Optionally set series metadata (icon, description, etc.)

### Adding Posts to a Series

1. Edit a post in the block editor
2. Use the Series panel in the sidebar to assign the post to a series
3. Set the post's part number and optional short title

### Using the Blocks

#### Series Navigation Block

Add the Series Navigation block to display previous/next links within a series. Options include:

- Show/hide series name
- Show/hide part numbers
- Customize previous/next labels
- Choose arrow style (arrow, chevron, or none)

#### Series Post List Block

Add the Series Post List block to display all posts in the current series. Options include:

- Use ordered or unordered list display
- Show/hide short titles
- Highlight current post
- Show/hide series title and icon

## Project Structure

```
content-series/
├── .github/workflows/  # CI workflows
├── assets/             # Admin quick-edit assets
├── bin/scripts/        # Local environment helper scripts
├── build/              # Generated JS/CSS build artifacts
├── includes/           # PHP classes
│   ├── class-admin.php
│   ├── class-block-bindings.php
│   ├── class-block-variations.php
│   ├── class-content-series.php
│   ├── class-migration.php
│   ├── class-post-meta.php
│   ├── class-rest-api.php
│   ├── class-taxonomy.php
│   ├── class-templates.php
│   └── class-term-meta.php
├── src/                # Source files
│   ├── blocks/         # Block definitions
│   │   ├── series-navigation/
│   │   └── series-post-list/
│   ├── bindings/       # Block bindings source registration
│   ├── sidebar/        # Editor sidebar components
│   ├── variations/     # Block variations
│   └── types/          # TypeScript type definitions
├── tests/              # Test files (PHP, JS setup, E2E)
├── jest.config.js      # Jest configuration
├── playwright.config.js # Playwright configuration
├── content-series.php  # Main plugin file
├── composer.json       # PHP dependencies
├── package.json        # Node.js dependencies
├── tsconfig.json       # TypeScript config for source
├── tsconfig.tests.json # TypeScript config for tests
└── README.md           # This file
```

## Testing

The plugin supports both PHP and JavaScript tests.

### PHPUnit

```bash
pnpm test
```

Watch mode:

```bash
pnpm test:watch
```

### JavaScript Unit Tests (Jest)

```bash
pnpm test:js
```

Watch mode:

```bash
pnpm test:js:watch
```

Coverage report:

```bash
pnpm test:js:coverage
```

### End-to-End Tests (Playwright)

Install Playwright browsers once:

```bash
pnpm exec playwright install
```

Start `wp-env` first (`pnpm env:start`), then run:

```bash
pnpm test:e2e
```

Playwright UI mode:

```bash
pnpm test:e2e:ui
```

By default, E2E tests target `http://localhost:8888` and log in with the
standard wp-env credentials (`admin` / `password`) unless overridden via
environment variables.

## Code Standards

- **PHP**: Follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- **JavaScript/TypeScript**: Follows WordPress JavaScript coding standards
- **CSS**: Follows WordPress CSS coding standards

Linting is enforced via PHPCS and wp-scripts. Run `pnpm lint:php:fix` to auto-fix PHP issues.

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run checks (`pnpm check-types && pnpm test && pnpm test:js && pnpm lint:php && pnpm lint:js`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2024

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## Credits

Built with modern WordPress development tools:

- [@wordpress/scripts](https://www.npmjs.com/package/@wordpress/scripts)
- [@wordpress/env](https://www.npmjs.com/package/@wordpress/env)
- [PHPUnit](https://phpunit.de/)
- [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
