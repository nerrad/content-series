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

use Content_Series\Post_Meta;
use Content_Series\Term_Meta;
use Content_Series\Rest_API;

// Get the current post ID from context.
$content_series_post_id = $block->context['postId'] ?? get_the_ID();

if ( ! $content_series_post_id ) {
	return '';
}

// Get the post's series.
$content_series_terms = get_the_terms( $content_series_post_id, CONTENT_SERIES_TAXONOMY );

if ( ! $content_series_terms || is_wp_error( $content_series_terms ) ) {
	// Not in a series, render nothing.
	return '';
}

// Use the first series (primary).
$content_series_series = $content_series_terms[0];

// Get posts in the series.
$content_series_posts = Rest_API::query_series_posts( $content_series_series->term_id );

if ( empty( $content_series_posts ) ) {
	return '';
}

// Block attributes.
$content_series_show_numbers      = $attributes['showNumbers'] ?? true;
$content_series_show_short_title  = $attributes['showShortTitle'] ?? false;
$content_series_highlight_current = $attributes['highlightCurrent'] ?? true;
$content_series_show_series_title = $attributes['showSeriesTitle'] ?? true;
$content_series_show_series_icon  = $attributes['showSeriesIcon'] ?? true;

// Get series icon.
$content_series_icon = Term_Meta::get_series_icon( $content_series_series->term_id );

// Build wrapper attributes.
$content_series_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-block-content-series-post-list',
	)
);

?>
<div <?php echo wp_kses_post( $content_series_wrapper_attributes ); ?>>
	<?php if ( $content_series_show_series_title ) : ?>
		<div class="wp-block-content-series-post-list__header">
			<?php if ( $content_series_show_series_icon && $content_series_icon ) : ?>
				<img
					src="<?php echo esc_url( $content_series_icon ); ?>"
					alt=""
					class="wp-block-content-series-post-list__icon"
				>
			<?php endif; ?>
			<h3 class="wp-block-content-series-post-list__title">
				<a href="<?php echo esc_url( get_term_link( $content_series_series ) ); ?>">
					<?php echo esc_html( $content_series_series->name ); ?>
				</a>
			</h3>
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
