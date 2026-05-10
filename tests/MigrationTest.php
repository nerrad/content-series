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
	 * Create a series term.
	 *
	 * @param string $name Series term name.
	 * @return int Created term ID.
	 */
	private function create_series_term( $name ) {
		$term_id = $this->factory->term->create(
			array(
				'taxonomy' => 'series',
				'name'     => $name,
			)
		);

		$this->assertIsInt( $term_id );

		return $term_id;
	}

	/**
	 * Create the legacy icons table.
	 */
	private function create_legacy_icons_table() {
		global $wpdb;

		$wpdb->query(
			"CREATE TABLE `{$this->legacy_icons_table}` (
				term_id INT NOT NULL,
				icon VARCHAR(255) NOT NULL,
				PRIMARY KEY (term_id)
			)"
		);
	}

	/**
	 * Insert a legacy icon row.
	 *
	 * @param int    $term_id Series term ID.
	 * @param string $icon Legacy icon value.
	 */
	private function insert_legacy_icon( $term_id, $icon ) {
		global $wpdb;

		$wpdb->insert(
			$this->legacy_icons_table,
			array(
				'term_id' => $term_id,
				'icon'    => $icon,
			),
			array( '%d', '%s' )
		);
	}

	/**
	 * Prime migration by adding the legacy option.
	 */
	private function add_legacy_options() {
		update_option( Migration::LEGACY_OPTIONS_KEY, array( 'active' => true ) );
	}

	/**
	 * Test that mixed-case legacy tables and root-relative icon paths migrate.
	 */
	public function test_migrates_mixed_case_legacy_icon_table_with_root_relative_icon_path() {
		$term_id = $this->create_series_term( 'Icon Series' );
		$icon    = 'wp-content/uploads/2011/01/UKRAINEMISSIONSLOGO-300x300.jpg';

		$this->add_legacy_options();
		$this->create_legacy_icons_table();
		$this->insert_legacy_icon( $term_id, $icon );

		$migration = new Migration();
		$this->assertTrue( $migration->maybe_migrate() );

		$this->assertSame(
			home_url( '/' . $icon ),
			get_term_meta( $term_id, Term_Meta::ICON_META_KEY, true )
		);
	}

	/**
	 * Test that absolute icon URLs pass through unchanged.
	 */
	public function test_migrates_absolute_icon_url_without_rewriting() {
		$term_id = $this->create_series_term( 'Absolute Icon Series' );
		$icon    = 'https://example.com/wp-content/uploads/icon.png';

		$this->add_legacy_options();
		$this->create_legacy_icons_table();
		$this->insert_legacy_icon( $term_id, $icon );

		$migration = new Migration();
		$this->assertTrue( $migration->maybe_migrate() );

		$this->assertSame(
			$icon,
			get_term_meta( $term_id, Term_Meta::ICON_META_KEY, true )
		);
	}

	/**
	 * Test that bare filenames preserve the legacy series_icons location.
	 */
	public function test_migrates_bare_icon_filename_to_legacy_series_icons_url() {
		$term_id = $this->create_series_term( 'Bare Filename Series' );
		$icon    = 'my-icon.png';

		$this->add_legacy_options();
		$this->create_legacy_icons_table();
		$this->insert_legacy_icon( $term_id, $icon );

		$migration = new Migration();
		$this->assertTrue( $migration->maybe_migrate() );

		$upload_dir = wp_upload_dir();

		$this->assertSame(
			trailingslashit( $upload_dir['baseurl'] ) . 'series_icons/' . $icon,
			get_term_meta( $term_id, Term_Meta::ICON_META_KEY, true )
		);
	}

	/**
	 * Test that missing legacy icon tables still complete migration.
	 */
	public function test_migration_completes_when_legacy_icons_table_is_missing() {
		$term_id = $this->create_series_term( 'No Table Series' );

		$this->add_legacy_options();

		$migration = new Migration();
		$this->assertTrue( $migration->maybe_migrate() );
		$this->assertSame( '', get_term_meta( $term_id, Term_Meta::ICON_META_KEY, true ) );
	}

	/**
	 * Test that migration does not run after it has already completed.
	 */
	public function test_migration_does_not_overwrite_icons_after_already_migrated() {
		global $wpdb;

		$term_id    = $this->create_series_term( 'Already Migrated Series' );
		$first_icon = 'wp-content/uploads/2011/01/first-icon.png';

		$this->add_legacy_options();
		$this->create_legacy_icons_table();
		$this->insert_legacy_icon( $term_id, $first_icon );

		$migration = new Migration();
		$this->assertTrue( $migration->maybe_migrate() );

		$wpdb->update(
			$this->legacy_icons_table,
			array(
				'icon' => 'wp-content/uploads/2011/01/second-icon.png',
			),
			array(
				'term_id' => $term_id,
			),
			array( '%s' ),
			array( '%d' )
		);

		$this->assertFalse( $migration->maybe_migrate() );
		$this->assertSame(
			home_url( '/' . $first_icon ),
			get_term_meta( $term_id, Term_Meta::ICON_META_KEY, true )
		);
	}
}
