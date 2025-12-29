<?php
/**
 * Admin functionality for Content Series.
 *
 * @package ContentSeries
 */

namespace Content_Series;

/**
 * Handles admin menu and admin-specific functionality.
 */
class Admin {

	/**
	 * Initialize admin hooks.
	 */
	public function init() {
		add_shortcode( 'content_series_catalog', array( $this, 'render_catalog_shortcode' ) );
	}

	/**
	 * Redirect main menu page to taxonomy page.
	 */
	public function redirect_to_taxonomy() {
		// Check capabilities.
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'content-series' ) );
		}

		wp_safe_redirect( admin_url( 'edit-tags.php?taxonomy=series' ) );
		exit;
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
