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
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_variation_assets' ) );
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
