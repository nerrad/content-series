<?php
/**
 * Plugin Name: Content Series
 * Plugin URI: https://github.com/your-username/content-series
 * Description: A modern, block-editor-native plugin for managing content series. Group posts together into series with full Gutenberg integration.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://your-site.com
 * Text Domain: content-series
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 8.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package ContentSeries
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'CONTENT_SERIES_VERSION', '1.0.0' );
define( 'CONTENT_SERIES_FILE', __FILE__ );
define( 'CONTENT_SERIES_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONTENT_SERIES_URL', plugin_dir_url( __FILE__ ) );

// Legacy compatibility constants (match PublishPress Series).
define( 'CONTENT_SERIES_PART_KEY', '_series_part' );
define( 'CONTENT_SERIES_SHORT_TITLE_KEY', '_spost_short_title' );
define( 'CONTENT_SERIES_TAXONOMY', 'series' );

/**
 * Autoloader for plugin classes.
 *
 * @param string $class_name The class name to load.
 */
spl_autoload_register(
	function ( $class_name ) {
		// Only autoload our classes.
		if ( strpos( $class_name, 'Content_Series\\' ) !== 0 ) {
			return;
		}

		// Convert namespace to file path.
		$class_file = str_replace( 'Content_Series\\', '', $class_name );
		$class_file = str_replace( '_', '-', $class_file );
		$class_file = strtolower( $class_file );
		$class_file = 'class-' . $class_file . '.php';

		$file_path = CONTENT_SERIES_PATH . 'includes/' . $class_file;

		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}
);

/**
 * Initialize the plugin.
 */
function content_series_init() {
	// Load plugin classes.
	$plugin = new Content_Series\Content_Series();
	$plugin->init();
}
add_action( 'plugins_loaded', 'content_series_init' );

/**
 * Plugin activation hook.
 */
function content_series_activate() {
	// Run migration if coming from legacy plugin.
	require_once CONTENT_SERIES_PATH . 'includes/class-migration.php';
	$migration = new Content_Series\Migration();
	$migration->maybe_migrate();

	// Flush rewrite rules for taxonomy.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'content_series_activate' );

/**
 * Plugin deactivation hook.
 */
function content_series_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'content_series_deactivate' );
