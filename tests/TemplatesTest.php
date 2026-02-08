<?php
/**
 * Tests for Templates class.
 *
 * @package ContentSeries
 */

namespace Content_Series\Tests;

use Content_Series\Post_Meta;
use Content_Series\Templates;
use WP_UnitTestCase;

/**
 * Test suite for Templates class.
 */
class TemplatesTest extends WP_UnitTestCase {

	/**
	 * Create a series term with a valid nonce for test environments.
	 *
	 * @param string $name Series term name.
	 * @return int Created term ID.
	 */
	private function create_series_term( $name ) {
		$_POST['_wpnonce_add-tag'] = wp_create_nonce( 'add-tag' );

		$term_id = $this->factory->term->create(
			array(
				'taxonomy' => 'series',
				'name'     => $name,
			)
		);

		unset( $_POST['_wpnonce_add-tag'] );

		return $term_id;
	}

	/**
	 * Test that archive series information markup is generated for series posts.
	 */
	public function test_archive_series_information_markup_for_series_post() {
		$post_id = $this->factory->post->create();
		$term_id = $this->create_series_term( 'Testing Series' );

		wp_set_object_terms( $post_id, $term_id, 'series' );
		Post_Meta::set_post_series_part( $post_id, $term_id, 4 );

		$markup = Templates::get_archive_series_information_markup( $post_id );

		$this->assertNotSame( '', $markup );
		$this->assertStringContainsString( 'content-series-archive-info', $markup );
		$this->assertStringContainsString( 'Testing Series', $markup );
		$this->assertStringContainsString( 'Part 4', $markup );
	}

	/**
	 * Test that empty string is returned when post has no series.
	 */
	public function test_archive_series_information_markup_without_series() {
		$post_id = $this->factory->post->create();

		$markup = Templates::get_archive_series_information_markup( $post_id );

		$this->assertSame( '', $markup );
	}

	/**
	 * Test that empty string is returned for invalid post id.
	 */
	public function test_archive_series_information_markup_with_invalid_post_id() {
		$markup = Templates::get_archive_series_information_markup( 0 );

		$this->assertSame( '', $markup );
	}

	/**
	 * Test that block rendering appends series info in archive contexts even when not main query.
	 */
	public function test_post_block_append_in_non_main_archive_query() {
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Archive Query Loop Post',
				'post_content' => 'Post content for archive test.',
			)
		);
		$term_id = $this->create_series_term( 'Block Query Series' );

		wp_set_object_terms( $post_id, $term_id, 'series' );
		Post_Meta::set_post_series_part( $post_id, $term_id, 2 );

		// Set archive-like context via search page.
		$this->go_to( '/?s=archive' );
		$this->assertTrue( is_search() );

		global $wp_query;
		$original_wp_query = $wp_query;

		$secondary_query = new \WP_Query(
			array(
				's' => 'archive',
			)
		);
		$wp_query = $secondary_query;

		try {
			$this->assertFalse( is_main_query() );
			$this->assertTrue( is_search() );

			$templates = new Templates();
			$content   = '<p>Rendered excerpt.</p>';
			$block     = array(
				'context' => array(
					'postId' => $post_id,
				),
			);

			$result = $templates->append_archive_series_information_for_post_block( $content, $block );

			$this->assertStringContainsString( 'content-series-archive-info', $result );
			$this->assertStringContainsString( 'Block Query Series', $result );
			$this->assertStringContainsString( 'Part 2', $result );
		} finally {
			$wp_query = $original_wp_query;
			wp_reset_postdata();
		}
	}
}
