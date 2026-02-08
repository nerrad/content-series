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

require_once dirname( __DIR__ ) . '/shared/series-context.php';

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

foreach ( $block->parsed_block['innerBlocks'] ?? array() as $content_series_inner_block ) {
	$content_series_inner_html .= ( new WP_Block( $content_series_inner_block, $content_series_render_context ) )->render();
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
