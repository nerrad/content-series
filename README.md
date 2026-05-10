# Content Series

A modern, block-editor-native WordPress plugin for managing content series. Group posts together into series with full Gutenberg integration.

## Features

- **Block Editor Native**: Built specifically for the WordPress block editor (Gutenberg)
- **Series Taxonomy**: Organize posts into series using a custom `series` taxonomy
- **Five Custom Blocks**:
  - **Series Post List** — ordered list of all posts in the current series, with configurable headers
  - **Series Navigation** — wrapper block for previous/next links and series metadata
  - **Series Navigation Link** — previous or next post link with arrows, part numbers, and titles
  - **Series Title** — displays the current series name as a heading, optionally linked to the archive
  - **Series Icon** — displays the series icon image, optionally linked to the archive
- **Editor Sidebar**: Assign posts to a series, set part/order numbers, and add optional short titles
- **Quick Edit Support**: Edit series part numbers directly from the posts list table
- **Series Catalog**: A dedicated `/series/` page listing all series in a grid with icons, descriptions, and post counts (powered by a `core/terms-query` block variation)
- **Series Archive Templates**: Block-based templates for individual series archives and the series catalog page
- **Block Bindings**: A `content-series/term-meta` binding source for connecting blocks to series metadata (e.g. binding an image block to the series icon)
- **Archive Enhancements**: Automatically appends series information ("Series: Name — Part N") to post excerpts on home, archive, and search pages
- **REST API**: Custom endpoints for listing series posts in order and bulk-reordering
- **PublishPress Series Migration**: Automatic data migration from the PublishPress Series plugin on activation (shared taxonomy slug, icon import)

## Requirements

- WordPress 6.9 or higher
- PHP 8.1 or higher
- Node.js and pnpm (for development)

## Installation

### From a Release (recommended)

1. Download the latest `content-series-{version}.zip` from the [Releases](https://github.com/nerrad/content-series/releases) page
2. In WordPress, go to **Plugins > Add New > Upload Plugin**
3. Upload the zip file and click **Install Now**
4. Activate the plugin

### From Source

1. Clone the repository into your plugins directory:

   ```bash
   cd wp-content/plugins
   git clone https://github.com/nerrad/content-series.git
   ```

2. Install dependencies and build:

   ```bash
   cd content-series
   pnpm install
   pnpm build
   ```

3. Activate the plugin through the WordPress admin panel.

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
- `pnpm check-types:tests` - Run TypeScript checks for test files

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
2. Create a new series with a name, slug, and description
3. Optionally upload a series icon image

### Adding Posts to a Series

1. Edit a post in the block editor
2. Open the **Series** panel in the post settings sidebar
3. Assign the post to a series
4. Set the post's **part number** (order within the series)
5. Optionally set a **short title** for use in series listings

Part numbers can also be edited via **Quick Edit** on the Posts list table.

### Blocks

All blocks are designed for use in post templates or individual posts.

#### Series Post List

Displays an ordered list of all posts in the current series, with links. Useful for showing readers the full series at a glance.

- Show/hide part numbers
- Use full titles or short titles
- Highlight the current post
- Supports a customizable header area (via inner blocks) for the series title and icon

#### Series Navigation

A wrapper block that provides previous/next navigation within a series. Add child blocks inside it:

- **Series Navigation Link** — a previous or next link. Options:
  - Direction: previous or next
  - Show/hide post title
  - Show/hide part numbers
  - Arrow style: arrow, chevron, or none
  - Custom label text
- **Series Title** — displays the series name as a heading (h1–h6), optionally linked to the series archive
- **Series Icon** — displays the series icon image, optionally linked to the series archive

#### Series Catalog

The plugin registers a `/series/` page that displays all series in a grid layout. Each series shows its icon, name, post count, and description. This uses a `core/terms-query` block variation (`content-series/catalog`) and a block template.

### Series Archives

Each series automatically gets an archive page at `/series/{slug}/` showing the series title, description, and a post list. These archives use block templates registered by the plugin.

On non-series archive pages (home, category, tag, search), posts that belong to a series automatically display a "Series: Name — Part N" link below their excerpt.

### REST API

The plugin provides custom REST API endpoints under the `content-series/v1` namespace:

- **`GET /content-series/v1/series/{id}/posts`** — returns all posts in a series, ordered by part number
- **`POST /content-series/v1/series/{id}/reorder`** — bulk-update post order within a series (requires `edit_posts` capability)
- **`GET /content-series/v1/series`** — returns all series with metadata including icon URLs

The standard WordPress REST API endpoint at `/wp/v2/series` is also available with enhanced responses that include icon data.

### Migrating from PublishPress Series

If you previously used the PublishPress Series plugin, Content Series automatically migrates your data on activation:

- Series terms and post assignments are preserved (both plugins use the `series` taxonomy slug)
- Series icons are imported from the legacy `orgseriesicons` table into standard term meta
- A one-time admin notice confirms migration results

## Project Structure

```
content-series/
├── .github/workflows/  # CI and release workflows
├── assets/             # Admin quick-edit CSS/JS
├── bin/scripts/        # Local environment helper scripts
├── build/              # Generated JS/CSS build artifacts
├── includes/           # PHP classes
│   ├── class-admin.php             # Quick edit support
│   ├── class-block-bindings.php    # Term meta binding source
│   ├── class-block-variations.php  # Series catalog variation
│   ├── class-content-series.php    # Main plugin orchestrator
│   ├── class-migration.php         # PublishPress Series migration
│   ├── class-post-meta.php         # Post meta (part number, short title)
│   ├── class-rest-api.php          # Custom REST endpoints
│   ├── class-taxonomy.php          # Series taxonomy registration
│   ├── class-templates.php         # Block templates and archive display
│   └── class-term-meta.php         # Term meta (series icons)
├── src/                # TypeScript/TSX source files
│   ├── blocks/         # Block definitions
│   │   ├── series-icon/
│   │   ├── series-navigation/
│   │   ├── series-navigation-link/
│   │   ├── series-post-list/
│   │   └── series-title/
│   ├── bindings/       # Block bindings (client-side)
│   ├── sidebar/        # Editor sidebar panel
│   ├── types/          # TypeScript type definitions
│   └── variations/     # Block variations (client-side)
├── tests/              # PHPUnit, Jest, and Playwright tests
├── content-series.php  # Main plugin file
├── uninstall.php       # Cleanup on plugin deletion
├── .distignore         # Files excluded from release zips
└── README.md
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

## Releases

Release zips are built automatically by GitHub Actions when a new release is published.

### Creating a Release

1. Go to **[Releases](https://github.com/nerrad/content-series/releases)** on GitHub
2. Click **Draft a new release**
3. Create a new tag (e.g. `v1.0.0`) targeting the `trunk` branch
4. Add a release title and description
5. Click **Publish release**

The workflow will automatically:
- Check out the tagged commit
- Install dependencies and build assets (`pnpm build`)
- Assemble a `content-series-{version}.zip` containing only distribution files
- Attach the zip to the release as a downloadable asset

### Testing a Build

To test the zip build without creating a release, trigger the workflow manually:

1. Go to **Actions > Release** on GitHub
2. Click **Run workflow**
3. Download the zip from the workflow run's artifacts

### What's in the Zip

The release zip contains a `content-series/` directory with:
- `content-series.php` and `uninstall.php`
- `includes/` (PHP classes)
- `build/` (compiled block JS/CSS)
- `assets/` (admin CSS/JS)
- `README.md`

Development files (source, tests, configs, node_modules) are excluded via `.distignore`.

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run checks (`pnpm check-types && pnpm check-types:tests && pnpm test && pnpm test:js && pnpm lint:php && pnpm lint:js`)
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
