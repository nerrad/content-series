<?php
/**
 * Main plugin class.
 *
 * @package ContentSeries
 */

namespace Content_Series;

/**
 * Main Content Series plugin class.
 */
class Content_Series {

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		// Initialize components.
		$this->init_taxonomy();
		$this->init_post_meta();
		$this->init_term_meta();
		$this->init_rest_api();
		$this->init_blocks();
		$this->init_templates();

		// Admin-only components.
		if ( is_admin() ) {
			$this->init_admin();
			$this->init_migration();
		}

		// Enqueue editor assets.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Initialize taxonomy.
	 */
	private function init_taxonomy() {
		require_once CONTENT_SERIES_PATH . 'includes/class-taxonomy.php';
		$taxonomy = new Taxonomy();
		$taxonomy->init();
	}

	/**
	 * Initialize post meta.
	 */
	private function init_post_meta() {
		require_once CONTENT_SERIES_PATH . 'includes/class-post-meta.php';
		$post_meta = new Post_Meta();
		$post_meta->init();
	}

	/**
	 * Initialize term meta.
	 */
	private function init_term_meta() {
		require_once CONTENT_SERIES_PATH . 'includes/class-term-meta.php';
		$term_meta = new Term_Meta();
		$term_meta->init();
	}

	/**
	 * Initialize REST API extensions.
	 */
	private function init_rest_api() {
		require_once CONTENT_SERIES_PATH . 'includes/class-rest-api.php';
		$rest_api = new Rest_API();
		$rest_api->init();
	}

	/**
	 * Initialize blocks.
	 */
	private function init_blocks() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Initialize block templates.
	 */
	private function init_templates() {
		require_once CONTENT_SERIES_PATH . 'includes/class-templates.php';
		$templates = new Templates();
		$templates->init();
	}

	/**
	 * Initialize admin components.
	 */
	private function init_admin() {
		require_once CONTENT_SERIES_PATH . 'includes/class-admin.php';
		$admin = new Admin();
		$admin->init();
	}

	/**
	 * Initialize migration system.
	 */
	private function init_migration() {
		require_once CONTENT_SERIES_PATH . 'includes/class-migration.php';
		$migration = new Migration();
		add_action( 'admin_init', array( $migration, 'maybe_migrate' ) );
		add_action( 'admin_notices', array( $migration, 'migration_notice' ) );
	}

	/**
	 * Register blocks.
	 */
	public function register_blocks() {
		// Series Post List block.
		if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( 'content-series/post-list' ) ) {
			register_block_type(
				CONTENT_SERIES_PATH . 'build/blocks/series-post-list',
				array(
					'render_callback' => array( $this, 'render_series_post_list' ),
				)
			);
		}

		// Series Navigation block.
		if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( 'content-series/navigation' ) ) {
			register_block_type(
				CONTENT_SERIES_PATH . 'build/blocks/series-navigation',
				array(
					'render_callback' => array( $this, 'render_series_navigation' ),
				)
			);
		}
	}

	/**
	 * Enqueue editor assets (sidebar panel).
	 */
	public function enqueue_editor_assets() {
		$asset_file = CONTENT_SERIES_PATH . 'build/sidebar/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'content-series-sidebar',
			CONTENT_SERIES_URL . 'build/sidebar/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'content-series-sidebar',
			CONTENT_SERIES_URL . 'build/sidebar/index.css',
			array(),
			$asset['version']
		);

		// Pass data to JavaScript.
		wp_localize_script(
			'content-series-sidebar',
			'contentSeriesData',
			array(
				'taxonomy'      => CONTENT_SERIES_TAXONOMY,
				'partKey'       => CONTENT_SERIES_PART_KEY,
				'shortTitleKey' => CONTENT_SERIES_SHORT_TITLE_KEY,
			)
		);
	}

	/**
	 * Render Series Post List block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block HTML.
	 */
	public function render_series_post_list( $attributes, $content, $block ) {
		// Include render file.
		$render_file = CONTENT_SERIES_PATH . 'src/blocks/series-post-list/render.php';
		if ( file_exists( $render_file ) ) {
			ob_start();
			include $render_file;
			return ob_get_clean();
		}
		return '';
	}

	/**
	 * Render Series Navigation block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block HTML.
	 */
	public function render_series_navigation( $attributes, $content, $block ) {
		// Include render file.
		$render_file = CONTENT_SERIES_PATH . 'src/blocks/series-navigation/render.php';
		if ( file_exists( $render_file ) ) {
			ob_start();
			include $render_file;
			return ob_get_clean();
		}
		return '';
	}
}
