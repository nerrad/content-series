<?php
/**
 * Server-side rendering for the Series Title block.
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

$content_series_series = $content_series_context['series'];
$content_series_level  = isset( $attributes['level'] ) ? absint( $attributes['level'] ) : 3;

if ( $content_series_level < 1 || $content_series_level > 6 ) {
	$content_series_level = 3;
}

$content_series_tag_name = sprintf( 'h%d', $content_series_level );
$content_series_is_link  = $attributes['isLink'] ?? true;
$content_series_term_url = get_term_link( $content_series_series );

$content_series_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-block-content-series-series-title wp-block-content-series-navigation__series',
	)
);

?>
<div <?php echo wp_kses_post( $content_series_wrapper_attributes ); ?>>
	<<?php echo esc_html( $content_series_tag_name ); ?> class="wp-block-content-series-title__heading">
		<?php if ( $content_series_is_link && ! is_wp_error( $content_series_term_url ) ) : ?>
			<a href="<?php echo esc_url( $content_series_term_url ); ?>">
				<?php echo esc_html( $content_series_series->name ); ?>
			</a>
		<?php else : ?>
			<span><?php echo esc_html( $content_series_series->name ); ?></span>
		<?php endif; ?>
	</<?php echo esc_html( $content_series_tag_name ); ?>>
</div>
<?php
