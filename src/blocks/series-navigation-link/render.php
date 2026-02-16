<?php
/**
 * Server-side rendering for the Series Navigation Link block.
 *
 * @package ContentSeries
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

use Content_Series\Post_Meta;

require_once CONTENT_SERIES_PATH . 'includes/series-context.php';

$content_series_direction = $attributes['direction'] ?? 'previous';

if ( ! in_array( $content_series_direction, array( 'previous', 'next' ), true ) ) {
	return '';
}

$content_series_context = content_series_get_series_context( $block );

if ( ! $content_series_context ) {
	return '';
}

$content_series_series      = $content_series_context['series'];
$content_series_is_previous = 'previous' === $content_series_direction;
$content_series_target_post = $content_series_is_previous
	? $content_series_context['prev_post']
	: $content_series_context['next_post'];
$content_series_class_name  = $content_series_is_previous
	? 'wp-block-content-series-navigation__prev'
	: 'wp-block-content-series-navigation__next';

$content_series_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => $content_series_class_name,
	)
);

if ( ! $content_series_target_post ) {
	ob_start();
	?>
	<div <?php echo wp_kses_post( $content_series_wrapper_attributes ); ?>>
		<span class="wp-block-content-series-navigation__placeholder">&nbsp;</span>
	</div>
	<?php
	return ob_get_clean();
}

$content_series_show_title        = $attributes['showTitle'] ?? true;
$content_series_show_part_numbers = $attributes['showPartNumbers'] ?? true;
$content_series_label             = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';
$content_series_arrow_style       = $attributes['arrowStyle'] ?? 'arrow';

if ( '' === $content_series_label ) {
	$content_series_label = $content_series_is_previous
		? __( 'Previous', 'content-series' )
		: __( 'Next', 'content-series' );
}

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

$content_series_arrow = $content_series_arrows[ $content_series_arrow_style ] ?? $content_series_arrows['arrow'];
$content_series_part  = Post_Meta::get_post_series_part( $content_series_target_post->ID, $content_series_series->term_id );

ob_start();
?>
<div <?php echo wp_kses_post( $content_series_wrapper_attributes ); ?>>
	<a href="<?php echo esc_url( get_permalink( $content_series_target_post->ID ) ); ?>">
		<?php if ( $content_series_is_previous && $content_series_arrow['prev'] ) : ?>
			<span class="wp-block-content-series-navigation__arrow">
				<?php echo esc_html( $content_series_arrow['prev'] ); ?>
			</span>
		<?php endif; ?>

		<span class="wp-block-content-series-navigation__text">
			<span class="wp-block-content-series-navigation__label">
				<?php
				echo esc_html( $content_series_label );
				if ( $content_series_show_part_numbers ) {
					/* translators: %d: part number */
					echo esc_html( sprintf( __( ' (Part %d)', 'content-series' ), $content_series_part ) );
				}
				?>
			</span>

			<?php if ( $content_series_show_title ) : ?>
				<span class="wp-block-content-series-navigation__title">
					<?php echo esc_html( get_the_title( $content_series_target_post->ID ) ); ?>
				</span>
			<?php endif; ?>
		</span>

		<?php if ( ! $content_series_is_previous && $content_series_arrow['next'] ) : ?>
			<span class="wp-block-content-series-navigation__arrow">
				<?php echo esc_html( $content_series_arrow['next'] ); ?>
			</span>
		<?php endif; ?>
	</a>
</div>
<?php
return ob_get_clean();
