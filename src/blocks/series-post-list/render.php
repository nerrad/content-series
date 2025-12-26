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

use ContentSeries\Post_Meta;
use ContentSeries\Term_Meta;
use ContentSeries\Rest_API;

// Get the current post ID from context.
$post_id = $block->context['postId'] ?? get_the_ID();

if ( ! $post_id ) {
	return '';
}

// Get the post's series.
$series_terms = get_the_terms( $post_id, CONTENT_SERIES_TAXONOMY );

if ( ! $series_terms || is_wp_error( $series_terms ) ) {
	// Not in a series, render nothing.
	return '';
}

// Use the first series (primary).
$series = $series_terms[0];

// Get posts in the series.
$series_posts = Rest_API::query_series_posts( $series->term_id );

if ( empty( $series_posts ) ) {
	return '';
}

// Block attributes.
$show_numbers       = $attributes['showNumbers'] ?? true;
$show_short_title   = $attributes['showShortTitle'] ?? false;
$highlight_current  = $attributes['highlightCurrent'] ?? true;
$show_series_title  = $attributes['showSeriesTitle'] ?? true;
$show_series_icon   = $attributes['showSeriesIcon'] ?? true;

// Get series icon.
$series_icon = Term_Meta::get_series_icon( $series->term_id );

// Build wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes( array(
	'class' => 'wp-block-content-series-post-list',
) );

?>
<div <?php echo $wrapper_attributes; ?>>
	<?php if ( $show_series_title ) : ?>
		<div class="wp-block-content-series-post-list__header">
			<?php if ( $show_series_icon && $series_icon ) : ?>
				<img
					src="<?php echo esc_url( $series_icon ); ?>"
					alt=""
					class="wp-block-content-series-post-list__icon"
				>
			<?php endif; ?>
			<h3 class="wp-block-content-series-post-list__title">
				<a href="<?php echo esc_url( get_term_link( $series ) ); ?>">
					<?php echo esc_html( $series->name ); ?>
				</a>
			</h3>
		</div>
	<?php endif; ?>

	<?php if ( $show_numbers ) : ?>
		<ol class="wp-block-content-series-post-list__items">
	<?php else : ?>
		<ul class="wp-block-content-series-post-list__items">
	<?php endif; ?>

	<?php foreach ( $series_posts as $series_post ) :
		$is_current = $series_post->ID === $post_id;
		$short_title = get_post_meta( $series_post->ID, CONTENT_SERIES_SHORT_TITLE_KEY, true );
		$title = ( $show_short_title && $short_title ) ? $short_title : get_the_title( $series_post->ID );
		$class = ( $is_current && $highlight_current ) ? 'is-current' : '';
	?>
		<li class="<?php echo esc_attr( $class ); ?>">
			<?php if ( $is_current && $highlight_current ) : ?>
				<span><?php echo esc_html( $title ); ?></span>
			<?php else : ?>
				<a href="<?php echo esc_url( get_permalink( $series_post->ID ) ); ?>">
					<?php echo esc_html( $title ); ?>
				</a>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>

	<?php if ( $show_numbers ) : ?>
		</ol>
	<?php else : ?>
		</ul>
	<?php endif; ?>
</div>
