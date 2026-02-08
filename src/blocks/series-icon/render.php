<?php
/**
 * Server-side rendering for the Series Icon block.
 *
 * @package ContentSeries
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

use Content_Series\Term_Meta;

require_once dirname( __DIR__ ) . '/shared/series-context.php';

$content_series_context = content_series_get_series_context( $block );

if ( ! $content_series_context ) {
	return '';
}

$content_series_series = $content_series_context['series'];
$content_series_icon   = Term_Meta::get_series_icon( $content_series_series->term_id );

if ( ! $content_series_icon ) {
	return '';
}

$content_series_is_link  = $attributes['isLink'] ?? true;
$content_series_size     = isset( $attributes['size'] ) ? absint( $attributes['size'] ) : 40;
$content_series_alt      = isset( $attributes['alt'] ) ? trim( (string) $attributes['alt'] ) : '';
$content_series_term_url = get_term_link( $content_series_series );

if ( $content_series_size < 16 ) {
	$content_series_size = 16;
}

if ( $content_series_size > 256 ) {
	$content_series_size = 256;
}

if ( '' === $content_series_alt ) {
	/* translators: %s: series name */
	$content_series_alt = sprintf( __( '%s icon', 'content-series' ), $content_series_series->name );
}

$content_series_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-block-content-series-series-icon wp-block-content-series-navigation__series-icon',
	)
);

ob_start();
?>
<div <?php echo wp_kses_post( $content_series_wrapper_attributes ); ?>>
	<?php if ( $content_series_is_link && ! is_wp_error( $content_series_term_url ) ) : ?>
		<a href="<?php echo esc_url( $content_series_term_url ); ?>">
	<?php endif; ?>
		<img
			src="<?php echo esc_url( $content_series_icon ); ?>"
			alt="<?php echo esc_attr( $content_series_alt ); ?>"
			class="wp-block-content-series-series-icon__image wp-block-content-series-navigation__icon"
			width="<?php echo esc_attr( (string) $content_series_size ); ?>"
			height="<?php echo esc_attr( (string) $content_series_size ); ?>"
			loading="lazy"
			decoding="async"
		/>
	<?php if ( $content_series_is_link && ! is_wp_error( $content_series_term_url ) ) : ?>
		</a>
	<?php endif; ?>
</div>
<?php
return ob_get_clean();
