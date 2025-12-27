<?php
/**
 * Uninstall script for Content Series plugin.
 *
 * @package ContentSeries
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data on uninstall.
 *
 * Note: By default, we preserve series data since it's valuable content organization.
 * Series taxonomy terms, post-series relationships, and post meta are NOT deleted.
 *
 * Only plugin-specific options are removed.
 */

// Delete plugin options.
delete_option( 'content_series_migrated' );
delete_option( 'content_series_migration_notice_dismissed' );

// Optionally delete all series data (commented out by default for safety).
// Uncomment the following code if you want a complete cleanup.

/**
 * Global $wpdb;

// Delete all series term meta.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->termmeta} WHERE meta_key IN (%s, %s)",
		'series_icon',
		'series_icon_id'
	)
);

// Delete all series post meta.
$wpdb->query(
	"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_series_part_%'"
);

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
		'_spost_short_title'
	)
);

// Delete series taxonomy.
$terms = get_terms( array(
	'taxonomy'   => 'series',
	'hide_empty' => false,
	'fields'     => 'ids',
) );

if ( ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term_id ) {
		wp_delete_term( $term_id, 'series' );
	}
}
*/

// Flush rewrite rules.
flush_rewrite_rules();
