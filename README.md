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
- **Editor Integration**: Sidebar panel for managing series directly in the block editor
- **Legacy Compatibility**: Compatible with the PublishPress Series plugin (uses the same taxonomy slug)
- **REST API Support**: Full REST API integration for headless WordPress setups
- **Custom Templates**: Archive and catalog templates for series display

## Requirements

- WordPress 6.8 or higher
- PHP 8.1 or higher
- Node.js and pnpm (for development)

## Installation

1. Download or clone this repository into your WordPress plugins directory:

   ```bash
   cd wp-content/plugins
   git clone https://github.com/your-username/content-series.git
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
   git clone https://github.com/your-username/content-series.git
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
- `pnpm lint:php` - Lint PHP files using PHPCS
- `pnpm lint:php:fix` - Auto-fix PHP linting issues
- `pnpm format` - Format code using wp-scripts

#### Testing

- `pnpm test` - Run PHPUnit tests
- `pnpm test:watch` - Run PHPUnit tests in watch mode

#### Environment Management

- `pnpm env:start` - Start wp-env environment
- `pnpm env:stop` - Stop wp-env environment
- `pnpm env:clean` - Clean wp-env environment
- `pnpm env:destroy` - Destroy wp-env environment

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

- Show/hide series title
- Show/hide part numbers
- Customize previous/next labels
- Choose arrow style (arrow, chevron, or none)

#### Series Post List Block

Add the Series Post List block to display all posts in the current series. Options include:

- Show/hide part numbers
- Show/hide short titles
- Highlight current post
- Show/hide series title and icon

### Templates

The plugin includes custom templates for series display:

- `taxonomy-series.php` - Archive template for series terms
- `series-catalog.php` - Catalog template for listing all series

## Project Structure

```
content-series/
├── build/              # Built assets (not in git)
├── includes/           # PHP classes
│   ├── class-admin.php
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
│   ├── sidebar/        # Editor sidebar components
│   └── types/          # TypeScript type definitions
├── templates/          # PHP templates
├── tests/              # PHPUnit tests
├── content-series.php  # Main plugin file
├── composer.json       # PHP dependencies
├── package.json        # Node.js dependencies
└── README.md           # This file
```

## Testing

The plugin uses PHPUnit for testing. Tests are located in the `tests/` directory.

Run tests using:

```bash
pnpm test
```

Or in watch mode:

```bash
pnpm test:watch
```

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
4. Run tests and linting (`pnpm test && pnpm lint:php && pnpm lint:js`)
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
