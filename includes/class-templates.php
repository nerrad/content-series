<?php
/**
 * Block template registration for series archives.
 *
 * @package ContentSeries
 */

namespace ContentSeries;

/**
 * Handles block template registration for series archive pages.
 */
class Templates {

	/**
	 * Initialize template hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_templates' ), 20 );
		add_filter( 'template_include', array( $this, 'maybe_include_template' ), 99 );
		add_filter( 'get_the_archive_title', array( $this, 'series_archive_title' ) );
		add_action( 'pre_get_posts', array( $this, 'series_catalog_query' ) );

		// Register rewrite rule for series catalog.
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}

	/**
	 * Add rewrite rules for series catalog page.
	 */
	public function add_rewrite_rules() {
		// Add rule for /series (without trailing taxonomy term) to show catalog.
		add_rewrite_rule(
			'^series/?$',
			'index.php?content_series_catalog=1',
			'top'
		);
	}

	/**
	 * Add query vars.
	 *
	 * @param array $vars Query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'content_series_catalog';
		return $vars;
	}

	/**
	 * Modify query for series catalog page.
	 *
	 * @param \WP_Query $query The query object.
	 */
	public function series_catalog_query( $query ) {
		if ( ! is_admin() && $query->is_main_query() && get_query_var( 'content_series_catalog' ) ) {
			// This is the series catalog page, we'll handle display in template.
			$query->set( 'post_type', 'post' );
			$query->set( 'posts_per_page', 0 );
		}
	}

	/**
	 * Register block templates for WordPress 6.7+.
	 */
	public function register_templates() {
		// Only for WordPress 6.7+ with template registry.
		if ( ! class_exists( 'WP_Block_Templates_Registry' ) ) {
			return;
		}

		$registry = \WP_Block_Templates_Registry::get_instance();

		// Individual series archive template.
		if ( ! $registry->is_registered( 'content-series//taxonomy-series' ) ) {
			$registry->register(
				'content-series//taxonomy-series',
				array(
					'title'       => __( 'Series Archive', 'content-series' ),
					'description' => __( 'Displays posts in a single series.', 'content-series' ),
					'content'     => $this->get_series_archive_template(),
				)
			);
		}

		// Series catalog template.
		if ( ! $registry->is_registered( 'content-series//archive-series-catalog' ) ) {
			$registry->register(
				'content-series//archive-series-catalog',
				array(
					'title'       => __( 'Series Catalog', 'content-series' ),
					'description' => __( 'Displays all series on the site.', 'content-series' ),
					'content'     => $this->get_series_catalog_template(),
				)
			);
		}
	}

	/**
	 * Maybe include plugin templates for non-block themes.
	 *
	 * @param string $template Current template path.
	 * @return string Template path to use.
	 */
	public function maybe_include_template( $template ) {
		// Series catalog page.
		if ( get_query_var( 'content_series_catalog' ) ) {
			$plugin_template = CONTENT_SERIES_PATH . 'templates/series-catalog.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		// Individual series archive.
		if ( is_tax( CONTENT_SERIES_TAXONOMY ) ) {
			// Check theme first.
			$theme_template = locate_template( array( 'taxonomy-series.php' ) );
			if ( $theme_template ) {
				return $theme_template;
			}

			// Use plugin template.
			$plugin_template = CONTENT_SERIES_PATH . 'templates/taxonomy-series.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}

	/**
	 * Modify archive title for series.
	 *
	 * @param string $title Archive title.
	 * @return string Modified title.
	 */
	public function series_archive_title( $title ) {
		if ( is_tax( CONTENT_SERIES_TAXONOMY ) ) {
			$term = get_queried_object();
			/* translators: %s: series name */
			return sprintf( __( 'Series: %s', 'content-series' ), $term->name );
		}

		if ( get_query_var( 'content_series_catalog' ) ) {
			return __( 'All Series', 'content-series' );
		}

		return $title;
	}

	/**
	 * Get template content for individual series archive.
	 *
	 * @return string Block template content.
	 */
	private function get_series_archive_template() {
		return '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">

<!-- wp:query-title {"type":"archive","showPrefix":false} /-->

<!-- wp:term-description /-->

<!-- wp:content-series/post-list {"showNumbers":true,"highlightCurrent":false,"context":"archive"} /-->

</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
	}

	/**
	 * Get template content for series catalog page.
	 *
	 * @return string Block template content.
	 */
	private function get_series_catalog_template() {
		return '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">

<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">' . esc_html__( 'All Series', 'content-series' ) . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Browse all content series on this site.', 'content-series' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[content_series_catalog]
<!-- /wp:shortcode -->

</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
	}
}
