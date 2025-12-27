<?php
/**
 * Template for displaying a single series archive.
 *
 * @package ContentSeries
 */

use Content_Series\Term_Meta;
use Content_Series\Rest_API;

get_header();

$content_series_term  = get_queried_object();
$content_series_icon  = Term_Meta::get_series_icon( $content_series_term->term_id );
$content_series_posts = Rest_API::query_series_posts( $content_series_term->term_id );
?>

<main id="primary" class="site-main content-series-archive">
	<header class="content-series-archive__header">
		<?php if ( $content_series_icon ) : ?>
			<img
				src="<?php echo esc_url( $content_series_icon ); ?>"
				alt=""
				class="content-series-archive__icon"
			>
		<?php endif; ?>

		<div class="content-series-archive__info">
			<h1 class="content-series-archive__title">
				<?php echo esc_html( $content_series_term->name ); ?>
			</h1>

			<?php if ( $content_series_term->description ) : ?>
				<div class="content-series-archive__description">
					<?php echo wp_kses_post( wpautop( $content_series_term->description ) ); ?>
				</div>
			<?php endif; ?>

			<p class="content-series-archive__count">
				<?php
				$content_series_posts_count = count( $content_series_posts );
				printf(
					esc_html(
						/* translators: %d: number of posts in series */
						_n(
							'%d post in this series',
							'%d posts in this series',
							$content_series_posts_count,
							'content-series'
						)
					),
					esc_html( $content_series_posts_count )
				);
				?>
			</p>
		</div>
	</header>

	<?php if ( ! empty( $content_series_posts ) ) : ?>
		<ol class="content-series-archive__posts">
			<?php foreach ( $content_series_posts as $content_series_index => $content_series_post ) : ?>
				<li class="content-series-archive__post">
					<article>
						<h2 class="content-series-archive__post-title">
							<a href="<?php echo esc_url( get_permalink( $content_series_post->ID ) ); ?>">
								<?php echo esc_html( get_the_title( $content_series_post->ID ) ); ?>
							</a>
						</h2>

						<div class="content-series-archive__post-meta">
							<time datetime="<?php echo esc_attr( get_the_date( 'c', $content_series_post->ID ) ); ?>">
								<?php echo esc_html( get_the_date( '', $content_series_post->ID ) ); ?>
							</time>
						</div>

						<?php if ( has_excerpt( $content_series_post->ID ) ) : ?>
							<div class="content-series-archive__post-excerpt">
								<?php echo wp_kses_post( get_the_excerpt( $content_series_post->ID ) ); ?>
							</div>
						<?php endif; ?>
					</article>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php else : ?>
		<p class="content-series-archive__no-posts">
			<?php esc_html_e( 'No posts found in this series.', 'content-series' ); ?>
		</p>
	<?php endif; ?>
</main>

<style>
.content-series-archive {
	max-width: 800px;
	margin: 0 auto;
	padding: 2rem 1rem;
}

.content-series-archive__header {
	display: flex;
	gap: 2rem;
	margin-bottom: 3rem;
	padding-bottom: 2rem;
	border-bottom: 1px solid #e2e4e7;
}

.content-series-archive__icon {
	width: 150px;
	height: 150px;
	object-fit: cover;
	border-radius: 8px;
	flex-shrink: 0;
}

.content-series-archive__title {
	margin: 0 0 1rem;
	font-size: 2rem;
}

.content-series-archive__description {
	color: #555;
	margin-bottom: 1rem;
}

.content-series-archive__description p:last-child {
	margin-bottom: 0;
}

.content-series-archive__count {
	color: #666;
	font-size: 0.9rem;
	margin: 0;
}

.content-series-archive__posts {
	list-style: none;
	padding: 0;
	margin: 0;
	counter-reset: series-counter;
}

.content-series-archive__post {
	counter-increment: series-counter;
	padding: 1.5rem 0;
	border-bottom: 1px solid #e2e4e7;
	position: relative;
	padding-left: 3rem;
}

.content-series-archive__post::before {
	content: counter(series-counter);
	position: absolute;
	left: 0;
	top: 1.5rem;
	width: 2rem;
	height: 2rem;
	background: #f0f0f0;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-weight: bold;
	font-size: 0.9rem;
	color: #333;
}

.content-series-archive__post-title {
	margin: 0 0 0.5rem;
	font-size: 1.25rem;
}

.content-series-archive__post-title a {
	text-decoration: none;
	color: inherit;
}

.content-series-archive__post-title a:hover {
	text-decoration: underline;
}

.content-series-archive__post-meta {
	color: #666;
	font-size: 0.85rem;
	margin-bottom: 0.5rem;
}

.content-series-archive__post-excerpt {
	color: #555;
	font-size: 0.95rem;
}

.content-series-archive__no-posts {
	color: #666;
	font-style: italic;
}

@media (max-width: 600px) {
	.content-series-archive__header {
		flex-direction: column;
		align-items: center;
		text-align: center;
	}

	.content-series-archive__icon {
		width: 120px;
		height: 120px;
	}
}
</style>

<?php
get_footer();
