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
		$script = $this->get_variation_script();

		wp_add_inline_script(
			'wp-blocks',
			$script,
			'after'
		);
	}

	/**
	 * Get the JavaScript for registering the series catalog variation.
	 *
	 * @return string JavaScript code for variation registration.
	 */
	private function get_variation_script() {
		$title       = __( 'Series Catalog', 'content-series' );
		$description = __( 'Display all content series in a grid layout.', 'content-series' );

		// Inner blocks structure for the variation.
		$inner_blocks = $this->get_inner_blocks_json();

		return <<<JS
( function() {
	if ( typeof wp === 'undefined' || ! wp.blocks ) {
		return;
	}

	wp.blocks.registerBlockVariation( 'core/terms-query', {
		name: 'content-series/catalog',
		title: '{$title}',
		description: '{$description}',
		category: 'theme',
		keywords: [ 'series', 'catalog', 'list' ],
		attributes: {
			termQuery: {
				perPage: 100,
				taxonomy: 'series',
				order: 'asc',
				orderBy: 'name',
				include: [],
				hideEmpty: false,
				showNested: false,
				inherit: false
			}
		},
		isActive: function( blockAttributes ) {
			return blockAttributes.termQuery &&
				blockAttributes.termQuery.taxonomy === 'series';
		},
		innerBlocks: {$inner_blocks},
		scope: [ 'inserter', 'block' ]
	} );
} )();
JS;
	}

	/**
	 * Get the inner blocks JSON structure for the variation.
	 *
	 * @return string JSON-encoded inner blocks array.
	 */
	private function get_inner_blocks_json() {
		$inner_blocks = array(
			array(
				'core/term-template',
				array(
					'layout' => array(
						'type'        => 'grid',
						'columnCount' => 3,
					),
				),
				array(
					array(
						'core/group',
						array(
							'style'  => array(
								'spacing' => array(
									'blockGap' => '0.5rem',
								),
							),
							'layout' => array(
								'type'        => 'flex',
								'orientation' => 'vertical',
								'justifyContent' => 'center',
							),
						),
						array(
							array(
								'core/image',
								array(
									'width'    => '150px',
									'height'   => '150px',
									'scale'    => 'contain',
									'metadata' => array(
										'bindings' => array(
											'url' => array(
												'source' => 'content-series/term-meta',
												'args'   => array(
													'key' => 'series_icon',
												),
											),
										),
									),
								),
								array(),
							),
							array(
								'core/term-name',
								array(
									'isLink'    => true,
									'textAlign' => 'center',
									'level'     => 3,
								),
								array(),
							),
							array(
								'core/term-count',
								array(
									'textAlign' => 'center',
								),
								array(),
							),
							array(
								'core/term-description',
								array(
									'textAlign' => 'center',
								),
								array(),
							),
						),
					),
				),
			),
		);

		return wp_json_encode( $inner_blocks );
	}
}
