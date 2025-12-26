<?php
/**
 * Series taxonomy registration.
 *
 * @package ContentSeries
 */

namespace ContentSeries;

/**
 * Handles series taxonomy registration.
 */
class Taxonomy {

	/**
	 * Initialize taxonomy hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_taxonomy' ), 0 );
	}

	/**
	 * Register the series taxonomy.
	 *
	 * Uses the same taxonomy slug as the legacy PublishPress Series plugin
	 * to maintain compatibility with existing content.
	 */
	public function register_taxonomy() {
		// Don't register if already exists (e.g., from another plugin).
		if ( taxonomy_exists( CONTENT_SERIES_TAXONOMY ) ) {
			return;
		}

		$labels = array(
			'name'                       => _x( 'Series', 'taxonomy general name', 'content-series' ),
			'singular_name'              => _x( 'Series', 'taxonomy singular name', 'content-series' ),
			'search_items'               => __( 'Search Series', 'content-series' ),
			'popular_items'              => __( 'Popular Series', 'content-series' ),
			'all_items'                  => __( 'All Series', 'content-series' ),
			'edit_item'                  => __( 'Edit Series', 'content-series' ),
			'view_item'                  => __( 'View Series', 'content-series' ),
			'update_item'                => __( 'Update Series', 'content-series' ),
			'add_new_item'               => __( 'Add New Series', 'content-series' ),
			'new_item_name'              => __( 'New Series Name', 'content-series' ),
			'separate_items_with_commas' => __( 'Separate series with commas', 'content-series' ),
			'add_or_remove_items'        => __( 'Add or remove series', 'content-series' ),
			'choose_from_most_used'      => __( 'Choose from the most used series', 'content-series' ),
			'not_found'                  => __( 'No series found.', 'content-series' ),
			'no_terms'                   => __( 'No series', 'content-series' ),
			'items_list_navigation'      => __( 'Series list navigation', 'content-series' ),
			'items_list'                 => __( 'Series list', 'content-series' ),
			'menu_name'                  => __( 'Series', 'content-series' ),
			'back_to_items'              => __( '&larr; Go to Series', 'content-series' ),
			'item_link'                  => __( 'Series Link', 'content-series' ),
			'item_link_description'      => __( 'A link to a series', 'content-series' ),
		);

		$capabilities = array(
			'manage_terms' => 'manage_categories',
			'edit_terms'   => 'manage_categories',
			'delete_terms' => 'manage_categories',
			'assign_terms' => 'edit_posts',
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Group posts into a series.', 'content-series' ),
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true, // Required for block editor.
			'rest_base'          => 'series',
			'capabilities'       => $capabilities,
			'rewrite'            => array(
				'slug'       => 'series',
				'with_front' => false,
			),
			'query_var'          => 'series',
		);

		/**
		 * Filter the post types that support the series taxonomy.
		 *
		 * @param array $post_types Post types to register the taxonomy for.
		 */
		$post_types = apply_filters( 'content_series_post_types', array( 'post' ) );

		register_taxonomy( CONTENT_SERIES_TAXONOMY, $post_types, $args );
	}
}
