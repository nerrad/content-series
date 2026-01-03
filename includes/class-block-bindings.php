<?php
/**
 * Block Bindings registration for Content Series.
 *
 * @package ContentSeries
 */

namespace Content_Series;

/**
 * Handles Block Bindings API registration for term meta.
 */
class Block_Bindings {

	/**
	 * Initialize block bindings hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_bindings_sources' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_bindings' ) );
	}

	/**
	 * Register block bindings sources (server-side).
	 */
	public function register_bindings_sources() {
		// Only register if the function exists (WordPress 6.5+).
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			'content-series/term-meta',
			array(
				'label'              => __( 'Series Term Meta', 'content-series' ),
				'get_value_callback' => array( $this, 'get_term_meta_value' ),
				'uses_context'       => array( 'termId' ),
			)
		);
	}

	/**
	 * Enqueue client-side block bindings registration for the editor.
	 */
	public function enqueue_editor_bindings() {
		$asset_file = CONTENT_SERIES_PATH . 'build/bindings/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'content-series-bindings',
			CONTENT_SERIES_URL . 'build/bindings/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
	}

	/**
	 * Get term meta value for block bindings (server-side).
	 *
	 * @param array     $source_args    Source arguments containing the meta key.
	 * @param \WP_Block $block_instance The block instance.
	 * @param string    $attribute_name The attribute name being bound.
	 * @return mixed|null The meta value or null if not found.
	 */
	public function get_term_meta_value( $source_args, $block_instance, $attribute_name ) {
		// Ensure the 'key' argument is provided.
		if ( ! isset( $source_args['key'] ) ) {
			return null;
		}

		// Retrieve the term ID from the block context.
		$term_id = $block_instance->context['termId'] ?? null;
		if ( ! $term_id ) {
			return null;
		}

		$key = $source_args['key'];

		// Handle special case for series_icon - return URL for image blocks.
		if ( 'series_icon' === $key ) {
			return Term_Meta::get_series_icon( $term_id );
		}

		// For other meta keys, get the value directly.
		return get_term_meta( $term_id, $key, true );
	}
}
