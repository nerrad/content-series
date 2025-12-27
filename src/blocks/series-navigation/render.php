<?php
/**
 * Server-side rendering for the Series Navigation block.
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

// Find current post index.
$content_series_current_index = -1;

foreach ( $content_series_posts as $content_series_index => $content_series_post ) {
	if ( $content_series_post->ID === $content_series_post_id ) {
		$content_series_current_index = $content_series_index;
		break;
	}
}

if ( -1 === $content_series_current_index ) {
	return '';
}

// Get prev/next posts.
$content_series_prev_post = $content_series_current_index > 0 ? $content_series_posts[ $content_series_current_index - 1 ] : null;
$content_series_next_post = $content_series_current_index < count( $content_series_posts ) - 1 ? $content_series_posts[ $content_series_current_index + 1 ] : null;

// If no navigation needed, return empty.
if ( ! $content_series_prev_post && ! $content_series_next_post ) {
	return '';
}

// Block attributes.
$content_series_show_title        = $attributes['showTitle'] ?? true;
$content_series_show_series_name  = $attributes['showSeriesName'] ?? false;
$content_series_show_part_numbers = $attributes['showPartNumbers'] ?? true;
$content_series_prev_label        = $attributes['prevLabel'] ?? __( 'Previous', 'content-series' );
$content_series_next_label        = $attributes['nextLabel'] ?? __( 'Next', 'content-series' );
$content_series_arrow_style       = $attributes['arrowStyle'] ?? 'arrow';

// Arrow characters.
$content_series_arrows = array(
	'arrow'   => array(
		'prev' => '←',
		'next' => '→',
	),
	'chevron' => array(
		'prev' => '‹',
		'next' => '›',
	),
	'none'    => array(
		'prev' => '',
		'next' => '',
	),
);
$content_series_arrow  = $content_series_arrows[ $content_series_arrow_style ] ?? $content_series_arrows['arrow'];

// Build wrapper attributes.
$content_series_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-block-content-series-navigation',
	)
);

?>
<nav <?php echo wp_kses_post( $content_series_wrapper_attributes ); ?>>
	<div class="wp-block-content-series-navigation__prev">
		<?php
		if ( $content_series_prev_post ) :
			$content_series_prev_part = Post_Meta::get_post_series_part( $content_series_prev_post->ID, $content_series_series->term_id );
			?>
			<a href="<?php echo esc_url( get_permalink( $content_series_prev_post->ID ) ); ?>">
				<?php if ( $content_series_arrow['prev'] ) : ?>
					<span class="wp-block-content-series-navigation__arrow">
						<?php echo esc_html( $content_series_arrow['prev'] ); ?>
					</span>
				<?php endif; ?>
				<span class="wp-block-content-series-navigation__text">
					<span class="wp-block-content-series-navigation__label">
						<?php
						echo esc_html( $content_series_prev_label );
						if ( $content_series_show_part_numbers ) {
							/* translators: %d: part number */
							echo esc_html( sprintf( ' (Part %d)', $content_series_prev_part ) );
						}
						?>
					</span>
					<?php if ( $content_series_show_title ) : ?>
						<span class="wp-block-content-series-navigation__title">
							<?php echo esc_html( get_the_title( $content_series_prev_post->ID ) ); ?>
						</span>
					<?php endif; ?>
				</span>
			</a>
		<?php else : ?>
			<span class="wp-block-content-series-navigation__placeholder">&nbsp;</span>
		<?php endif; ?>
	</div>

	<?php if ( $content_series_show_series_name ) : ?>
		<div class="wp-block-content-series-navigation__series">
			<a href="<?php echo esc_url( get_term_link( $content_series_series ) ); ?>">
				<?php echo esc_html( $content_series_series->name ); ?>
			</a>
		</div>
	<?php endif; ?>

	<div class="wp-block-content-series-navigation__next">
		<?php
		if ( $content_series_next_post ) :
			$content_series_next_part = Post_Meta::get_post_series_part( $content_series_next_post->ID, $content_series_series->term_id );
			?>
			<a href="<?php echo esc_url( get_permalink( $content_series_next_post->ID ) ); ?>">
				<span class="wp-block-content-series-navigation__text">
					<span class="wp-block-content-series-navigation__label">
						<?php
						echo esc_html( $content_series_next_label );
						if ( $content_series_show_part_numbers ) {
							/* translators: %d: part number */
							echo esc_html( sprintf( ' (Part %d)', $content_series_next_part ) );
						}
						?>
					</span>
					<?php if ( $content_series_show_title ) : ?>
						<span class="wp-block-content-series-navigation__title">
							<?php echo esc_html( get_the_title( $content_series_next_post->ID ) ); ?>
						</span>
					<?php endif; ?>
				</span>
				<?php if ( $content_series_arrow['next'] ) : ?>
					<span class="wp-block-content-series-navigation__arrow">
						<?php echo esc_html( $content_series_arrow['next'] ); ?>
					</span>
				<?php endif; ?>
			</a>
		<?php else : ?>
			<span class="wp-block-content-series-navigation__placeholder">&nbsp;</span>
		<?php endif; ?>
	</div>
</nav>
