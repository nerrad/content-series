<?php
/**
 * Block Variations registration for Content Series.
 *
 * @package ContentSeries
 */

namespace Content_Series;

/**
 * Handles Block Variations registration for series catalog.
 */
class Block_Variations {

	/**
	 * Initialize block variations hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_variations' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_variation_assets' ) );
	}

	/**
	 * Register block variations via PHP.
	 *
	 * Note: Block variations for core blocks are typically registered via JavaScript.
	 * This method registers the variation server-side for better compatibility.
	 */
	public function register_variations() {
		// The variation is registered via JavaScript for better editor support.
		// See enqueue_variation_assets() for the JS registration.
	}

	/**
	 * Enqueue block variation assets for the editor.
	 */
	public function enqueue_variation_assets() {
		$asset_file = CONTENT_SERIES_PATH . 'build/variations/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'content-series-variations',
			CONTENT_SERIES_URL . 'build/variations/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// Pass data to JavaScript.
		wp_localize_script(
			'content-series-variations',
			'contentSeriesVariations',
			array(
				'title'       => __( 'Series Catalog', 'content-series' ),
				'description' => __( 'Display all content series in a grid layout.', 'content-series' ),
			)
		);
	}
}
