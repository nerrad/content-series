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
var_dump( $series_posts );

// Find current post index.
$current_index = -1;

foreach ( $series_posts as $index => $post ) {
	var_dump( $post->ID );
	if ( $post->ID === $post_id ) {
		$current_index = $index;
		break;
	}
}

if ( $current_index === -1 ) {
	return '';
}

// Get prev/next posts.
$prev_post = $current_index > 0 ? $series_posts[ $current_index - 1 ] : null;
$next_post = $current_index < count( $series_posts ) - 1 ? $series_posts[ $current_index + 1 ] : null;

// If no navigation needed, return empty.
if ( ! $prev_post && ! $next_post ) {
	return '';
}

// Block attributes.
$show_title        = $attributes['showTitle'] ?? true;
$show_series_name  = $attributes['showSeriesName'] ?? false;
$show_part_numbers = $attributes['showPartNumbers'] ?? true;
$prev_label        = $attributes['prevLabel'] ?? __( 'Previous', 'content-series' );
$next_label        = $attributes['nextLabel'] ?? __( 'Next', 'content-series' );
$arrow_style       = $attributes['arrowStyle'] ?? 'arrow';

// Arrow characters.
$arrows = array(
	'arrow'   => array( 'prev' => '←', 'next' => '→' ),
	'chevron' => array( 'prev' => '‹', 'next' => '›' ),
	'none'    => array( 'prev' => '', 'next' => '' ),
);
$arrow = $arrows[ $arrow_style ] ?? $arrows['arrow'];

// Build wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes( array(
	'class' => 'wp-block-content-series-navigation',
) );

?>
<nav <?php echo $wrapper_attributes; ?>>
	<div class="wp-block-content-series-navigation__prev">
		<?php if ( $prev_post ) :
			$prev_part = Post_Meta::get_post_series_part( $prev_post->ID, $series->term_id );
		?>
			<a href="<?php echo esc_url( get_permalink( $prev_post->ID ) ); ?>">
				<?php if ( $arrow['prev'] ) : ?>
					<span class="wp-block-content-series-navigation__arrow">
						<?php echo esc_html( $arrow['prev'] ); ?>
					</span>
				<?php endif; ?>
				<span class="wp-block-content-series-navigation__text">
					<span class="wp-block-content-series-navigation__label">
						<?php
						echo esc_html( $prev_label );
						if ( $show_part_numbers ) {
							/* translators: %d: part number */
							echo esc_html( sprintf( ' (Part %d)', $prev_part ) );
						}
						?>
					</span>
					<?php if ( $show_title ) : ?>
						<span class="wp-block-content-series-navigation__title">
							<?php echo esc_html( get_the_title( $prev_post->ID ) ); ?>
						</span>
					<?php endif; ?>
				</span>
			</a>
		<?php else : ?>
			<span class="wp-block-content-series-navigation__placeholder">&nbsp;</span>
		<?php endif; ?>
	</div>

	<?php if ( $show_series_name ) : ?>
		<div class="wp-block-content-series-navigation__series">
			<a href="<?php echo esc_url( get_term_link( $series ) ); ?>">
				<?php echo esc_html( $series->name ); ?>
			</a>
		</div>
	<?php endif; ?>

	<div class="wp-block-content-series-navigation__next">
		<?php if ( $next_post ) :
			$next_part = Post_Meta::get_post_series_part( $next_post->ID, $series->term_id );
		?>
			<a href="<?php echo esc_url( get_permalink( $next_post->ID ) ); ?>">
				<span class="wp-block-content-series-navigation__text">
					<span class="wp-block-content-series-navigation__label">
						<?php
						echo esc_html( $next_label );
						if ( $show_part_numbers ) {
							/* translators: %d: part number */
							echo esc_html( sprintf( ' (Part %d)', $next_part ) );
						}
						?>
					</span>
					<?php if ( $show_title ) : ?>
						<span class="wp-block-content-series-navigation__title">
							<?php echo esc_html( get_the_title( $next_post->ID ) ); ?>
						</span>
					<?php endif; ?>
				</span>
				<?php if ( $arrow['next'] ) : ?>
					<span class="wp-block-content-series-navigation__arrow">
						<?php echo esc_html( $arrow['next'] ); ?>
					</span>
				<?php endif; ?>
			</a>
		<?php else : ?>
			<span class="wp-block-content-series-navigation__placeholder">&nbsp;</span>
		<?php endif; ?>
	</div>
</nav>
