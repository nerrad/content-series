<?php
/**
 * Template for displaying the series catalog (all series).
 *
 * @package ContentSeries
 */

use ContentSeries\Term_Meta;

get_header();

$terms = get_terms( array(
	'taxonomy'   => CONTENT_SERIES_TAXONOMY,
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
) );
?>

<main id="primary" class="site-main content-series-catalog-page">
	<header class="content-series-catalog-page__header">
		<h1 class="content-series-catalog-page__title">
			<?php esc_html_e( 'All Series', 'content-series' ); ?>
		</h1>
		<p class="content-series-catalog-page__description">
			<?php esc_html_e( 'Browse all content series on this site.', 'content-series' ); ?>
		</p>
	</header>

	<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
		<div class="content-series-catalog-page__grid">
			<?php foreach ( $terms as $term ) :
				$icon = Term_Meta::get_series_icon( $term->term_id );
				$link = get_term_link( $term );
			?>
				<article class="content-series-catalog-page__item">
					<?php if ( $icon ) : ?>
						<a href="<?php echo esc_url( $link ); ?>" class="content-series-catalog-page__icon-link">
							<img
								src="<?php echo esc_url( $icon ); ?>"
								alt=""
								class="content-series-catalog-page__icon"
							>
						</a>
					<?php endif; ?>

					<h2 class="content-series-catalog-page__item-title">
						<a href="<?php echo esc_url( $link ); ?>">
							<?php echo esc_html( $term->name ); ?>
						</a>
					</h2>

					<p class="content-series-catalog-page__item-count">
						<?php
						printf(
							/* translators: %d: number of posts */
							esc_html( _n( '%d post', '%d posts', $term->count, 'content-series' ) ),
							$term->count
						);
						?>
					</p>

					<?php if ( $term->description ) : ?>
						<p class="content-series-catalog-page__item-description">
							<?php echo esc_html( wp_trim_words( $term->description, 20 ) ); ?>
						</p>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="content-series-catalog-page__no-series">
			<?php esc_html_e( 'No series found.', 'content-series' ); ?>
		</p>
	<?php endif; ?>
</main>

<style>
.content-series-catalog-page {
	max-width: 1200px;
	margin: 0 auto;
	padding: 2rem 1rem;
}

.content-series-catalog-page__header {
	text-align: center;
	margin-bottom: 3rem;
}

.content-series-catalog-page__title {
	margin: 0 0 0.5rem;
	font-size: 2.5rem;
}

.content-series-catalog-page__description {
	color: #666;
	font-size: 1.1rem;
	margin: 0;
}

.content-series-catalog-page__grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
	gap: 2rem;
}

.content-series-catalog-page__item {
	background: #f8f9fa;
	border-radius: 8px;
	padding: 1.5rem;
	text-align: center;
	transition: box-shadow 0.2s ease;
}

.content-series-catalog-page__item:hover {
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.content-series-catalog-page__icon-link {
	display: block;
	margin-bottom: 1rem;
}

.content-series-catalog-page__icon {
	width: 120px;
	height: 120px;
	object-fit: cover;
	border-radius: 8px;
	margin: 0 auto;
}

.content-series-catalog-page__item-title {
	margin: 0 0 0.5rem;
	font-size: 1.25rem;
}

.content-series-catalog-page__item-title a {
	text-decoration: none;
	color: inherit;
}

.content-series-catalog-page__item-title a:hover {
	text-decoration: underline;
}

.content-series-catalog-page__item-count {
	color: #666;
	font-size: 0.9rem;
	margin: 0 0 0.75rem;
}

.content-series-catalog-page__item-description {
	color: #555;
	font-size: 0.9rem;
	margin: 0;
	line-height: 1.5;
}

.content-series-catalog-page__no-series {
	text-align: center;
	color: #666;
	font-style: italic;
}
</style>

<?php
get_footer();
