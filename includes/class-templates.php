<?php
/**
 * Block template registration for series archives.
 *
 * @package ContentSeries
 */

namespace Content_Series;

/**
 * Handles block template registration for series archive pages.
 */
class Templates {

	/**
	 * Initialize template hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_templates' ), 20 );
		add_filter( 'get_the_archive_title', array( $this, 'series_archive_title' ) );
		add_action( 'pre_get_posts', array( $this, 'series_catalog_query' ) );
		add_filter( 'the_excerpt', array( $this, 'append_archive_series_information' ), 20 );
		add_filter( 'the_content', array( $this, 'append_archive_series_information' ), 20 );

		// Register rewrite rule for series catalog.
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

		// Resolve block templates for custom routes.
		// For custom query var routes, WordPress doesn't automatically resolve block templates,
		// so we need to manually provide the template.
		add_filter( 'template_include', array( $this, 'inject_block_template_for_custom_route' ), 99 );
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

			// Set query flags to make WordPress recognize this as an archive.
			// This helps with block template resolution.
			$query->is_archive           = true;
			$query->is_post_type_archive = false;
			$query->is_home              = false;
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

		/**
		 * Block templates registry instance.
		 *
		 * @var \WP_Block_Templates_Registry $registry
		 */
		$registry = \WP_Block_Templates_Registry::get_instance();

		// Individual series archive template.
		if ( ! $registry->is_registered( 'content-series//taxonomy-series' ) ) {
			/**
			 * Register series archive template.
			 *
			 * @phpstan-ignore-next-line
			 */
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
			/**
			 * Register series catalog template.
			 *
			 * @phpstan-ignore-next-line
			 */
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
	 * Inject block template for custom routes in block themes.
	 *
	 * For block themes, we need to manually provide our template since
	 * WordPress doesn't automatically resolve templates for custom query vars.
	 *
	 * @param string $template The template path WordPress is trying to load.
	 * @return string The template path (we modify globals for block themes).
	 */
	public function inject_block_template_for_custom_route( $template ) {
		// Only process on frontend requests.
		if ( is_admin() ) {
			return $template;
		}

		// Only handle block themes.
		if ( ! wp_is_block_theme() ) {
			return $template;
		}

		// Check if this is the series catalog route.
		if ( get_query_var( 'content_series_catalog' ) && function_exists( 'get_block_template' ) ) {
			// Get the template using WordPress's get_block_template() function.
			// This works with templates registered via the registry.
			$catalog_template = get_block_template( 'content-series//archive-series-catalog', 'wp_template' );
			if ( $catalog_template ) {
				// Set the global template variable that WordPress uses.
				// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				global $_wp_current_template, $_wp_current_template_content;

				$_wp_current_template = $catalog_template;

				// Also set the template content global.
				if ( isset( $catalog_template->content ) ) {
					$_wp_current_template_content = $catalog_template->content;
				}
				// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
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
	 * Append series information to post excerpts/content on non-series archive pages.
	 *
	 * @param string $content Post excerpt/content.
	 * @return string Post excerpt/content with series information.
	 */
	public function append_archive_series_information( $content ) {
		if ( ! $this->should_append_archive_series_information() ) {
			return $content;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $content;
		}

		$series_information = self::get_archive_series_information_markup( $post_id );
		if ( '' === $series_information ) {
			return $content;
		}

		// Prevent duplicate output if the same filter runs on already-formatted content.
		if ( false !== strpos( $content, 'content-series-archive-info' ) ) {
			return $content;
		}

		return $content . $series_information;
	}

	/**
	 * Determine whether to append series information for current loop context.
	 *
	 * @return bool True when series information should be appended.
	 */
	private function should_append_archive_series_information() {
		if ( is_admin() || is_feed() ) {
			return false;
		}

		if ( ! in_the_loop() || ! is_main_query() ) {
			return false;
		}

		if ( ! ( is_archive() || is_home() || is_search() ) ) {
			return false;
		}

		if ( is_tax( CONTENT_SERIES_TAXONOMY ) || get_query_var( 'content_series_catalog' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Build archive series information markup for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string HTML markup, or empty string when no series is assigned.
	 */
	public static function get_archive_series_information_markup( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return '';
		}

		$series_terms = get_the_terms( $post_id, CONTENT_SERIES_TAXONOMY );
		if ( ! $series_terms || is_wp_error( $series_terms ) ) {
			return '';
		}

		$series_term = reset( $series_terms );
		if ( ! $series_term instanceof \WP_Term ) {
			return '';
		}

		$series_link = get_term_link( $series_term );
		if ( is_wp_error( $series_link ) ) {
			return '';
		}

		$series_part = Post_Meta::get_post_series_part( $post_id, $series_term->term_id );

		/* translators: %d: series part number. */
		$part_label = sprintf( __( 'Part %d', 'content-series' ), $series_part );

		return sprintf(
			'<p class="content-series-archive-info">' .
				'<span class="content-series-archive-info__label">%1$s</span> ' .
				'<a class="content-series-archive-info__link" href="%2$s">%3$s</a> ' .
				'<span class="content-series-archive-info__part">%4$s</span>' .
			'</p>',
			esc_html__( 'Series:', 'content-series' ),
			esc_url( $series_link ),
			esc_html( $series_term->name ),
			esc_html( $part_label )
		);
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
	 * Uses the core Terms Query block with series taxonomy configuration.
	 *
	 * @return string Block template content.
	 */
	private function get_series_catalog_template() {
		$all_series_title = esc_html__( 'All Series', 'content-series' );
		$browse_text      = esc_html__( 'Browse all content series on this site.', 'content-series' );

		return '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">

<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">' . $all_series_title . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . $browse_text . '</p>
<!-- /wp:paragraph -->

<!-- wp:terms-query {"termQuery":{"perPage":100,"taxonomy":"series","order":"asc","orderBy":"name","include":[],"hideEmpty":false,"showNested":false,"inherit":false}} -->
<!-- wp:term-template {"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:group {"style":{"spacing":{"blockGap":"0.5rem"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group">
<!-- wp:image {"width":"150px","height":"150px","scale":"contain","metadata":{"bindings":{"url":{"source":"content-series/term-meta","args":{"key":"series_icon"}}}}} -->
<figure class="wp-block-image is-resized"><img src="" alt="" style="object-fit:contain;width:150px;height:150px"/></figure>
<!-- /wp:image -->

<!-- wp:term-name {"textAlign":"center","level":3,"isLink":true} /-->

<!-- wp:term-count {"textAlign":"center"} /-->

<!-- wp:term-description {"textAlign":"center"} /-->
</div>
<!-- /wp:group -->
<!-- /wp:term-template -->
<!-- /wp:terms-query -->

</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
	}
}
