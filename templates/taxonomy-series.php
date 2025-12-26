<?php
/**
 * Template for displaying a single series archive.
 *
 * @package ContentSeries
 */

use ContentSeries\Term_Meta;
use ContentSeries\Rest_API;

get_header();

$term = get_queried_object();
$icon = Term_Meta::get_series_icon( $term->term_id );
$posts = Rest_API::query_series_posts( $term->term_id );
?>

<main id="primary" class="site-main content-series-archive">
	<header class="content-series-archive__header">
		<?php if ( $icon ) : ?>
			<img
				src="<?php echo esc_url( $icon ); ?>"
				alt=""
				class="content-series-archive__icon"
			>
		<?php endif; ?>

		<div class="content-series-archive__info">
			<h1 class="content-series-archive__title">
				<?php echo esc_html( $term->name ); ?>
			</h1>

			<?php if ( $term->description ) : ?>
				<div class="content-series-archive__description">
					<?php echo wp_kses_post( wpautop( $term->description ) ); ?>
				</div>
			<?php endif; ?>

			<p class="content-series-archive__count">
				<?php
				printf(
					/* translators: %d: number of posts in series */
					esc_html( _n(
						'%d post in this series',
						'%d posts in this series',
						count( $posts ),
						'content-series'
					) ),
					count( $posts )
				);
				?>
			</p>
		</div>
	</header>

	<?php if ( ! empty( $posts ) ) : ?>
		<ol class="content-series-archive__posts">
			<?php foreach ( $posts as $index => $post ) : ?>
				<li class="content-series-archive__post">
					<article>
						<h2 class="content-series-archive__post-title">
							<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
								<?php echo esc_html( get_the_title( $post->ID ) ); ?>
							</a>
						</h2>

						<div class="content-series-archive__post-meta">
							<time datetime="<?php echo esc_attr( get_the_date( 'c', $post->ID ) ); ?>">
								<?php echo esc_html( get_the_date( '', $post->ID ) ); ?>
							</time>
						</div>

						<?php if ( has_excerpt( $post->ID ) ) : ?>
							<div class="content-series-archive__post-excerpt">
								<?php echo wp_kses_post( get_the_excerpt( $post->ID ) ); ?>
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
