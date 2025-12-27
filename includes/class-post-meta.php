<?php
/**
 * Post meta registration for series.
 *
 * @package ContentSeries
 */

namespace Content_Series;

/**
 * Handles post meta registration for series ordering and short titles.
 */
class Post_Meta {

	/**
	 * Initialize post meta hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );

		// Ensure meta is set when post is assigned to series (fallback for REST API edge cases).
		add_action( 'set_object_terms', array( $this, 'maybe_set_default_series_order' ), 10, 6 );
	}

	/**
	 * Register post meta fields.
	 *
	 * Note: The _series_part_{series_id} meta keys are dynamic and registered
	 * per-series. The base _series_part key is registered here for general access.
	 */
	public function register_meta() {
		// Register the short title meta (used in series listings).
		register_post_meta(
			'post',
			CONTENT_SERIES_SHORT_TITLE_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		// Register a generic series_order meta for REST API access.
		// The actual ordering uses _series_part_{series_id} format.
		register_post_meta(
			'post',
			'_content_series_order',
			array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'additionalProperties' => array(
							'type' => 'integer',
						),
					),
				),
				'sanitize_callback' => array( $this, 'sanitize_series_order' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Sanitize series order data.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array Sanitized series order array.
	 */
	public function sanitize_series_order( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $value as $series_id => $order ) {
			$sanitized[ absint( $series_id ) ] = absint( $order );
		}

		return $sanitized;
	}

	/**
	 * Register REST API fields for series order data.
	 */
	public function register_rest_fields() {
		register_rest_field(
			'post',
			'series_order',
			array(
				'get_callback'    => array( $this, 'get_series_order' ),
				'update_callback' => array( $this, 'update_series_order' ),
				'schema'          => array(
					'type'                 => 'object',
					'description'          => __( 'Series order data keyed by series ID.', 'content-series' ),
					'context'              => array( 'view', 'edit' ),
					'additionalProperties' => array(
						'type' => 'integer',
					),
				),
			)
		);
	}

	/**
	 * Get series order data for a post.
	 *
	 * @param array $post Post data array.
	 * @return array Series order data keyed by series ID.
	 */
	public function get_series_order( $post ) {
		$post_id = $post['id'];
		$series  = get_the_terms( $post_id, CONTENT_SERIES_TAXONOMY );
		$order   = array();

		if ( ! $series || is_wp_error( $series ) ) {
			return $order;
		}

		foreach ( $series as $term ) {
			$meta_key                = CONTENT_SERIES_PART_KEY . '_' . $term->term_id;
			$part                    = get_post_meta( $post_id, $meta_key, true );
			$order[ $term->term_id ] = $part ? absint( $part ) : 1;
		}

		return $order;
	}

	/**
	 * Update series order data for a post.
	 *
	 * @param array   $value   Series order data.
	 * @param WP_Post $post    Post object.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function update_series_order( $value, $post ) {
		if ( ! is_array( $value ) ) {
			return new \WP_Error(
				'invalid_series_order',
				__( 'Series order must be an object.', 'content-series' )
			);
		}

		foreach ( $value as $series_id => $order ) {
			$meta_key = CONTENT_SERIES_PART_KEY . '_' . absint( $series_id );
			update_post_meta( $post->ID, $meta_key, absint( $order ) );
		}

		return true;
	}

	/**
	 * Get the part number for a post in a specific series.
	 *
	 * @param int $post_id   Post ID.
	 * @param int $series_id Series term ID.
	 * @return int Part number (1 if not set).
	 */
	public static function get_post_series_part( $post_id, $series_id ) {
		$meta_key = CONTENT_SERIES_PART_KEY . '_' . $series_id;
		$part     = get_post_meta( $post_id, $meta_key, true );

		return $part ? absint( $part ) : 1;
	}

	/**
	 * Set the part number for a post in a specific series.
	 *
	 * @param int $post_id   Post ID.
	 * @param int $series_id Series term ID.
	 * @param int $part      Part number.
	 */
	public static function set_post_series_part( $post_id, $series_id, $part ) {
		$meta_key = CONTENT_SERIES_PART_KEY . '_' . $series_id;
		update_post_meta( $post_id, $meta_key, absint( $part ) );
	}

	/**
	 * Set default series order when post is assigned to series (if not already set).
	 * This is a fallback to ensure meta is set even if REST API field update doesn't run.
	 *
	 * @param int    $object_id  Object ID (post ID).
	 * @param array  $terms      Array of term IDs.
	 * @param array  $tt_ids     Array of term taxonomy IDs.
	 * @param string $taxonomy    Taxonomy slug.
	 * @param bool   $append     Whether to append terms.
	 * @param array  $old_tt_ids Old term taxonomy IDs.
	 */
	public function maybe_set_default_series_order( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		// Only handle series taxonomy.
		if ( CONTENT_SERIES_TAXONOMY !== $taxonomy ) {
			return;
		}

		// Only handle posts.
		$post_type = get_post_type( $object_id );
		if ( 'post' !== $post_type ) {
			return;
		}

		// Get current series terms for this post.
		$current_series = get_the_terms( $object_id, CONTENT_SERIES_TAXONOMY );
		if ( ! $current_series || is_wp_error( $current_series ) ) {
			return;
		}

		// For each series, ensure meta is set (default to 1 if not set).
		foreach ( $current_series as $term ) {
			$meta_key = CONTENT_SERIES_PART_KEY . '_' . $term->term_id;
			$existing = get_post_meta( $object_id, $meta_key, true );

			// Only set default if meta doesn't exist.
			if ( empty( $existing ) ) {
				update_post_meta( $object_id, $meta_key, 1 );
			}
		}
	}
}
