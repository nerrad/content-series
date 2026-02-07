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
	 * Test that archive series information markup is generated for series posts.
	 */
	public function test_archive_series_information_markup_for_series_post() {
		$post_id = $this->factory->post->create();
		$_POST['_wpnonce_add-tag'] = wp_create_nonce( 'add-tag' );
		$term_id = $this->factory->term->create(
			array(
				'taxonomy' => 'series',
				'name'     => 'Testing Series',
			)
		);
		unset( $_POST['_wpnonce_add-tag'] );

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
}
