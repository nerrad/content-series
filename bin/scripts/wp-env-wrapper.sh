#!/bin/bash

# wp-env wrapper script for shared environment across git worktrees
# This script sets up a shared wp-env environment that can be used
# across multiple worktrees, with a symlink pointing to the current worktree.

set -e

# Get the current worktree path (where the script was executed from)
# This should be the actual worktree directory, not the symlink target
CURRENT_WORKTREE="$(pwd -P)"

# Define shared environment directory
SHARED_ENV_DIR="$HOME/.wp-env-shared/content-series"
PLUGIN_SYMLINK="$SHARED_ENV_DIR/plugin"
WP_ENV_CONFIG="$SHARED_ENV_DIR/.wp-env.json"
ORIGINAL_CONFIG="$CURRENT_WORKTREE/.wp-env.json"

# Create shared directory if it doesn't exist
mkdir -p "$SHARED_ENV_DIR"

# Create or update symlink to current worktree
if [ -L "$PLUGIN_SYMLINK" ] || [ -e "$PLUGIN_SYMLINK" ]; then
	# Remove existing symlink or file
	rm -f "$PLUGIN_SYMLINK"
fi
ln -s "$CURRENT_WORKTREE" "$PLUGIN_SYMLINK"

# Read original .wp-env.json and create shared config
if [ -f "$ORIGINAL_CONFIG" ]; then
	# Use node to parse and modify JSON (more reliable than jq or grep)
	# Pass paths as arguments to avoid shell escaping issues
	node -e "
		const fs = require('fs');
		const originalConfig = process.argv[1];
		const sharedConfig = process.argv[2];
		const config = JSON.parse(fs.readFileSync(originalConfig, 'utf8'));
		config.plugins = ['./plugin'];
		fs.writeFileSync(sharedConfig, JSON.stringify(config, null, 2));
	" "$ORIGINAL_CONFIG" "$WP_ENV_CONFIG"
else
	# Fallback config if original doesn't exist
	cat > "$WP_ENV_CONFIG" <<EOF
{
  "phpVersion": "8.2",
  "testsPort": 8889,
  "port": 8888,
  "plugins": ["./plugin"],
  "config": {
    "WP_DEBUG": true,
    "WP_DEBUG_LOG": true,
    "SCRIPT_DEBUG": true
  }
}
EOF
fi

# Set WP_ENV_HOME to the shared directory
export WP_ENV_HOME="$SHARED_ENV_DIR"

# Change to shared directory and execute wp-env command
cd "$SHARED_ENV_DIR"

# Execute wp-env with all passed arguments
exec npx @wordpress/env "$@"

