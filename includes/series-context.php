<?php
/**
 * Shared series context helpers.
 *
 * @package ContentSeries
 */

use Content_Series\Rest_API;

if ( ! function_exists( 'content_series_resolve_post_id' ) ) {
	/**
	 * Resolve post ID from block context and common runtime fallbacks.
	 *
	 * @param WP_Block $block Block instance.
	 * @return int|null
	 */
	function content_series_resolve_post_id( $block ) {
		$post_id = 0;

		if ( $block instanceof WP_Block ) {
			$post_id = absint( $block->context['postId'] ?? 0 );
		}

		if ( ! $post_id ) {
			$post_id = absint( get_the_ID() );
		}

		if ( ! $post_id && isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ) {
			$post_id = absint( $GLOBALS['post']->ID );
		}

		if ( ! $post_id ) {
			$post_id = absint( get_queried_object_id() );
		}

		return $post_id > 0 ? $post_id : null;
	}
}

if ( ! function_exists( 'content_series_get_series_context' ) ) {
	/**
	 * Get series context data for the current post.
	 *
	 * @param WP_Block $block Block instance.
	 * @return array<string,mixed>|null
	 */
	function content_series_get_series_context( $block ) {
		$post_id = content_series_resolve_post_id( $block );

		if ( ! $post_id ) {
			return null;
		}

		static $cache = array();
		$cache_key    = (string) $post_id;

		if ( array_key_exists( $cache_key, $cache ) ) {
			return $cache[ $cache_key ];
		}

		$terms = get_the_terms( $post_id, CONTENT_SERIES_TAXONOMY );

		if ( ! $terms || is_wp_error( $terms ) ) {
			$cache[ $cache_key ] = null;
			return null;
		}

		$series = $terms[0];
		$posts  = Rest_API::query_series_posts( $series->term_id );

		if ( empty( $posts ) ) {
			$cache[ $cache_key ] = null;
			return null;
		}

		$current_index = -1;

		foreach ( $posts as $index => $post ) {
			if ( $post->ID === $post_id ) {
				$current_index = $index;
				break;
			}
		}

		if ( -1 === $current_index ) {
			$cache[ $cache_key ] = null;
			return null;
		}

		$cache[ $cache_key ] = array(
			'post_id'       => $post_id,
			'series'        => $series,
			'posts'         => $posts,
			'current_index' => $current_index,
			'prev_post'     => $current_index > 0 ? $posts[ $current_index - 1 ] : null,
			'next_post'     => $current_index < count( $posts ) - 1 ? $posts[ $current_index + 1 ] : null,
		);

		return $cache[ $cache_key ];
	}
}
