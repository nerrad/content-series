<?php
/**
 * Admin functionality for Content Series.
 *
 * @package ContentSeries
 */

namespace Content_Series;

/**
 * Handles admin menu, settings page, and admin-specific functionality.
 */
class Admin {

	/**
	 * Initialize admin hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_shortcode( 'content_series_catalog', array( $this, 'render_catalog_shortcode' ) );
	}

	/**
	 * Add admin menu items.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Series', 'content-series' ),
			__( 'Series', 'content-series' ),
			'manage_categories',
			'content-series',
			array( $this, 'render_settings_page' ),
			'dashicons-list-view',
			25
		);

		add_submenu_page(
			'content-series',
			__( 'Manage Series', 'content-series' ),
			__( 'Manage Series', 'content-series' ),
			'manage_categories',
			'content-series',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'content-series',
			__( 'All Series (Taxonomy)', 'content-series' ),
			__( 'All Series', 'content-series' ),
			'manage_categories',
			'edit-tags.php?taxonomy=series'
		);
	}

	/**
	 * Render the settings page container.
	 */
	public function render_settings_page() {
		// Check capabilities.
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'content-series' ) );
		}

		echo '<div id="content-series-settings" class="wrap"></div>';
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only on our settings page.
		if ( 'toplevel_page_content-series' !== $hook ) {
			return;
		}

		$asset_file = CONTENT_SERIES_PATH . 'build/settings/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			// Show fallback message if assets not built.
			add_action(
				'admin_notices',
				function () {
					?>
				<div class="notice notice-error">
					<p>
						<?php esc_html_e( 'Content Series assets have not been built. Please run `npm install && npm run build` in the plugin directory.', 'content-series' ); ?>
					</p>
				</div>
					<?php
				}
			);
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'content-series-settings',
			CONTENT_SERIES_URL . 'build/settings/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'content-series-settings',
			CONTENT_SERIES_URL . 'build/settings/index.css',
			array( 'wp-components' ),
			$asset['version']
		);

		// Localize data for React app.
		wp_localize_script(
			'content-series-settings',
			'contentSeriesAdmin',
			array(
				'restUrl'       => rest_url( 'content-series/v1/' ),
				'wpRestUrl'     => rest_url( 'wp/v2/' ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'taxonomyUrl'   => admin_url( 'edit-tags.php?taxonomy=series' ),
				'adminUrl'      => admin_url(),
				'pluginVersion' => CONTENT_SERIES_VERSION,
			)
		);
	}

	/**
	 * Render the series catalog shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_catalog_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'columns'    => 3,
				'show_count' => true,
				'show_icon'  => true,
			),
			$atts,
			'content_series_catalog'
		);

		$terms = get_terms(
			array(
				'taxonomy'   => CONTENT_SERIES_TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '<p>' . esc_html__( 'No series found.', 'content-series' ) . '</p>';
		}

		$columns = absint( $atts['columns'] );
		$output  = '<div class="content-series-catalog" style="display: grid; grid-template-columns: repeat(' . $columns . ', 1fr); gap: 2rem;">';

		foreach ( $terms as $term ) {
			$icon_url = Term_Meta::get_series_icon( $term->term_id );
			$link     = get_term_link( $term );

			$output .= '<div class="content-series-catalog__item" style="text-align: center;">';

			if ( $atts['show_icon'] && $icon_url ) {
				$output .= sprintf(
					'<a href="%s"><img src="%s" alt="%s" style="max-width: 150px; max-height: 150px; margin-bottom: 1rem;"></a>',
					esc_url( $link ),
					esc_url( $icon_url ),
					esc_attr( $term->name )
				);
			}

			$output .= sprintf(
				'<h3 style="margin: 0 0 0.5rem;"><a href="%s">%s</a></h3>',
				esc_url( $link ),
				esc_html( $term->name )
			);

			if ( $atts['show_count'] ) {
				$output .= sprintf(
					'<p style="margin: 0; color: #666;">%s</p>',
					/* translators: %d: number of posts */
					esc_html( sprintf( _n( '%d post', '%d posts', $term->count, 'content-series' ), $term->count ) )
				);
			}

			if ( $term->description ) {
				$output .= sprintf(
					'<p style="margin: 0.5rem 0 0; font-size: 0.9em;">%s</p>',
					esc_html( wp_trim_words( $term->description, 20 ) )
				);
			}

			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}
}
