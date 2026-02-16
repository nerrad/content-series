<?php
/**
 * Server-side rendering for the Series Post List block.
 *
 * @package ContentSeries
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

require_once CONTENT_SERIES_PATH . 'includes/series-context.php';

$content_series_context = content_series_get_series_context( $block );

if ( ! $content_series_context ) {
	return '';
}

$content_series_post_id   = $content_series_context['post_id'];
$content_series_posts     = $content_series_context['posts'];
$content_series_post_type = get_post_type( $content_series_post_id ) ?: 'post';

// Block attributes.
$content_series_show_numbers      = $attributes['showNumbers'] ?? true;
$content_series_show_short_title  = $attributes['showShortTitle'] ?? false;
$content_series_highlight_current = $attributes['highlightCurrent'] ?? true;

// Build wrapper attributes.
$content_series_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-block-content-series-post-list',
	)
);

$content_series_render_context = array_merge(
	$block->context,
	array(
		'postId'   => $content_series_post_id,
		'postType' => $content_series_post_type,
	)
);

// Determine rendering path: new (InnerBlocks) vs legacy (self-closing).
// Self-closing blocks have empty innerContent; paired blocks have non-empty innerContent.
$content_series_has_inner_blocks = ! empty( $block->parsed_block['innerContent'] );

$content_series_header_markup = '';

if ( $content_series_has_inner_blocks ) {
	// New path: render inner blocks (series-icon, series-title) from saved content.
	foreach ( $block->parsed_block['innerBlocks'] ?? array() as $content_series_inner_block ) {
		$content_series_header_markup .= ( new WP_Block( $content_series_inner_block, $content_series_render_context ) )->render();
	}
} else {
	// Legacy fallback: manually construct child blocks from attributes.
	$content_series_show_series_title = $attributes['showSeriesTitle'] ?? true;
	$content_series_show_series_icon  = $attributes['showSeriesIcon'] ?? true;

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

	if ( $content_series_show_series_title ) {
		if ( $content_series_show_series_icon ) {
			$content_series_header_markup .= $content_series_render_child_block(
				'content-series/series-icon',
				array(
					'isLink'    => true,
					'size'      => 60,
					'className' => 'wp-block-content-series-post-list__icon',
				),
				$content_series_render_context
			);
		}

		$content_series_header_markup .= $content_series_render_child_block(
			'content-series/series-title',
			array(
				'isLink'    => true,
				'level'     => 3,
				'className' => 'wp-block-content-series-post-list__title',
			),
			$content_series_render_context
		);
	}
}

?>
<div <?php echo wp_kses_post( $content_series_wrapper_attributes ); ?>>
	<?php if ( '' !== trim( wp_strip_all_tags( $content_series_header_markup ) ) ) : ?>
		<div class="wp-block-content-series-post-list__header">
			<?php echo $content_series_header_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php endif; ?>

	<?php if ( $content_series_show_numbers ) : ?>
		<ol class="wp-block-content-series-post-list__items">
	<?php else : ?>
		<ul class="wp-block-content-series-post-list__items">
	<?php endif; ?>

	<?php
	foreach ( $content_series_posts as $content_series_post ) :
		$content_series_is_current  = $content_series_post->ID === $content_series_post_id;
		$content_series_short_title = get_post_meta( $content_series_post->ID, CONTENT_SERIES_SHORT_TITLE_KEY, true );
		$content_series_title       = ( $content_series_show_short_title && $content_series_short_title ) ? $content_series_short_title : get_the_title( $content_series_post->ID );
		$content_series_class       = ( $content_series_is_current && $content_series_highlight_current ) ? 'is-current' : '';
		?>
		<li class="<?php echo esc_attr( $content_series_class ); ?>">
			<?php if ( $content_series_is_current && $content_series_highlight_current ) : ?>
				<span><?php echo esc_html( $content_series_title ); ?></span>
			<?php else : ?>
				<a href="<?php echo esc_url( get_permalink( $content_series_post->ID ) ); ?>">
					<?php echo esc_html( $content_series_title ); ?>
				</a>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>

	<?php if ( $content_series_show_numbers ) : ?>
		</ol>
	<?php else : ?>
		</ul>
	<?php endif; ?>
</div>
<?php
