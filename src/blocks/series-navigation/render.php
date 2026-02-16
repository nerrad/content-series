<?php
/**
 * Server-side rendering for the Series Navigation wrapper block.
 *
 * @package ContentSeries
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

require_once CONTENT_SERIES_PATH . 'includes/series-context.php';

// Build wrapper attributes.
$content_series_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-block-content-series-navigation',
	)
);

$content_series_post_id = content_series_resolve_post_id( $block );

if ( ! $content_series_post_id ) {
	return '';
}

$content_series_render_context = array_merge(
	$block->context,
	array(
		'postId'   => $content_series_post_id,
		'postType' => get_post_type( $content_series_post_id ) ?: 'post',
	)
);

$content_series_inner_html = '';

// Determine rendering path: new (InnerBlocks) vs legacy (self-closing).
// Self-closing blocks have empty innerContent; paired blocks have non-empty innerContent.
$content_series_has_inner_blocks = ! empty( $block->parsed_block['innerContent'] );

if ( $content_series_has_inner_blocks ) {
	// New path: render inner blocks from saved content.
	foreach ( $block->parsed_block['innerBlocks'] ?? array() as $content_series_inner_block ) {
		$content_series_inner_html .= ( new WP_Block( $content_series_inner_block, $content_series_render_context ) )->render();
	}
} else {
	// Legacy fallback: manually construct default child blocks matching the editor template.
	$content_series_render_child_block = static function ( $block_name, $block_attributes, $context ) {
		$parsed_block = array(
			'blockName'    => $block_name,
			'attrs'        => $block_attributes,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);

		$block_instance = new WP_Block( $parsed_block, $context );
		return $block_instance->render();
	};

	$content_series_inner_html .= $content_series_render_child_block(
		'content-series/navigation-link',
		array( 'direction' => 'previous' ),
		$content_series_render_context
	);

	$content_series_inner_html .= $content_series_render_child_block(
		'content-series/series-title',
		array(),
		$content_series_render_context
	);

	$content_series_inner_html .= $content_series_render_child_block(
		'content-series/navigation-link',
		array( 'direction' => 'next' ),
		$content_series_render_context
	);
}

if ( '' === trim( wp_strip_all_tags( $content_series_inner_html ) ) ) {
	return '';
}

ob_start();
?>
<nav <?php echo wp_kses_post( $content_series_wrapper_attributes ); ?>>
	<?php echo $content_series_inner_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</nav>
<?php
return ob_get_clean();
