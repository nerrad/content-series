<?php
/**
 * Tests for Taxonomy class.
 *
 * @package ContentSeries
 */

namespace Content_Series\Tests;

use WP_UnitTestCase;

/**
 * Test suite for Taxonomy class.
 */
class TaxonomyTest extends WP_UnitTestCase {

	/**
	 * Test that taxonomy is registered.
	 *
	 * The plugin is loaded via bootstrap.php on muplugins_loaded,
	 * and WordPress bootstrap fires init, so the taxonomy should be registered.
	 */
	public function test_taxonomy_registered() {
		$this->assertTrue( taxonomy_exists( 'series' ) );
	}

	/**
	 * Test that taxonomy can be assigned to posts.
	 */
	public function test_taxonomy_assigned_to_posts() {
		$object_types = get_object_taxonomies( 'post' );
		$this->assertContains( 'series', $object_types );
	}

	/**
	 * Test that series term can be created and assigned to post.
	 */
	public function test_series_term_assignment() {
		$post_id = $this->factory->post->create();
		$term_id = $this->factory->term->create(
			array(
				'taxonomy' => 'series',
				'name'     => 'Test Series',
			)
		);

		wp_set_object_terms( $post_id, $term_id, 'series' );

		$terms = wp_get_object_terms( $post_id, 'series' );
		$this->assertCount( 1, $terms );
		$this->assertEquals( 'Test Series', $terms[0]->name );
	}
}
