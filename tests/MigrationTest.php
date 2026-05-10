<?php
/**
 * Tests for Migration class.
 *
 * @package ContentSeries
 */

namespace Content_Series\Tests;

// phpcs:disable WordPress.Files.FileName -- Match the repository's existing *Test.php naming convention.
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Tests intentionally create a legacy custom table.

use Content_Series\Migration;
use Content_Series\Term_Meta;
use WP_UnitTestCase;

/**
 * Test suite for Migration class.
 */
class MigrationTest extends WP_UnitTestCase {

	/**
	 * Legacy icons table name for the current test.
	 *
	 * @var string
	 */
	private $legacy_icons_table;

	/**
	 * Set up test data.
	 */
	public function set_up() {
		parent::set_up();

		global $wpdb;

		$this->legacy_icons_table = $wpdb->prefix . 'orgSeriesIcons';

		delete_option( Migration::MIGRATION_OPTION );
		delete_option( Migration::LEGACY_OPTIONS_KEY );
		$wpdb->query( "DROP TABLE IF EXISTS `{$this->legacy_icons_table}`" );
	}

	/**
	 * Clean up test data.
	 */
	public function tear_down() {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS `{$this->legacy_icons_table}`" );
		delete_option( Migration::MIGRATION_OPTION );
		delete_option( Migration::LEGACY_OPTIONS_KEY );

		parent::tear_down();
	}

	/**
	 * Test that mixed-case legacy tables and root-relative icon paths migrate.
	 */
	public function test_migrates_mixed_case_legacy_icon_table_with_root_relative_icon_path() {
		global $wpdb;

		$term_id = $this->factory->term->create(
			array(
				'taxonomy' => 'series',
				'name'     => 'Icon Series',
			)
		);
		$icon    = 'wp-content/uploads/2011/01/UKRAINEMISSIONSLOGO-300x300.jpg';

		update_option( Migration::LEGACY_OPTIONS_KEY, array( 'active' => true ) );

		$wpdb->query(
			"CREATE TABLE `{$this->legacy_icons_table}` (
				term_id INT NOT NULL,
				icon VARCHAR(255) NOT NULL,
				PRIMARY KEY (term_id)
			)"
		);
		$wpdb->insert(
			$this->legacy_icons_table,
			array(
				'term_id' => $term_id,
				'icon'    => $icon,
			),
			array( '%d', '%s' )
		);

		$migration = new Migration();
		$this->assertTrue( $migration->maybe_migrate() );

		$this->assertSame(
			home_url( '/' . $icon ),
			get_term_meta( $term_id, Term_Meta::ICON_META_KEY, true )
		);
	}
}
