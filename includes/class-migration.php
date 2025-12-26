<?php
/**
 * Migration from legacy PublishPress Series plugin.
 *
 * @package ContentSeries
 */

namespace ContentSeries;

/**
 * Handles migration of data from the legacy PublishPress Series plugin.
 */
class Migration {

	/**
	 * Option key for tracking migration status.
	 */
	const MIGRATION_OPTION = 'content_series_migrated';

	/**
	 * Option key for migration notice dismissal.
	 */
	const NOTICE_DISMISSED_OPTION = 'content_series_migration_notice_dismissed';

	/**
	 * Legacy plugin options key.
	 */
	const LEGACY_OPTIONS_KEY = 'org_series_options';

	/**
	 * Legacy icons table name (without prefix).
	 */
	const LEGACY_ICONS_TABLE = 'orgseriesicons';

	/**
	 * Run migration if needed.
	 *
	 * @return bool True if migration ran, false otherwise.
	 */
	public function maybe_migrate() {
		// Already migrated.
		if ( get_option( self::MIGRATION_OPTION ) ) {
			return false;
		}

		// Check if legacy plugin was installed.
		$legacy_options = get_option( self::LEGACY_OPTIONS_KEY );
		if ( ! $legacy_options ) {
			// No legacy data, mark as migrated.
			update_option( self::MIGRATION_OPTION, array(
				'version'   => CONTENT_SERIES_VERSION,
				'timestamp' => time(),
				'source'    => 'fresh_install',
			) );
			return false;
		}

		// Run migration.
		$this->migrate_icons();

		// Mark as migrated.
		update_option( self::MIGRATION_OPTION, array(
			'version'      => CONTENT_SERIES_VERSION,
			'timestamp'    => time(),
			'source'       => 'publishpress_series',
			'icons_count'  => $this->get_migrated_icons_count(),
		) );

		return true;
	}

	/**
	 * Migrate icons from legacy custom table to term meta.
	 */
	private function migrate_icons() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::LEGACY_ICONS_TABLE;

		// Check if legacy table exists.
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		if ( ! $table_exists ) {
			return;
		}

		// Get all icons from legacy table.
		$icons = $wpdb->get_results(
			"SELECT term_id, icon FROM {$table_name}"
		);

		if ( ! $icons ) {
			return;
		}

		foreach ( $icons as $icon ) {
			$term_id  = absint( $icon->term_id );
			$icon_url = $icon->icon;

			// Skip if invalid.
			if ( ! $term_id || empty( $icon_url ) ) {
				continue;
			}

			// Skip if term no longer exists.
			$term = get_term( $term_id, CONTENT_SERIES_TAXONOMY );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}

			// Check if icon URL is a full URL or just a filename.
			if ( strpos( $icon_url, 'http' ) !== 0 ) {
				// It's likely a relative path or filename, try to construct full URL.
				$upload_dir = wp_upload_dir();
				$icon_url   = trailingslashit( $upload_dir['baseurl'] ) . 'series_icons/' . $icon_url;
			}

			// Save to term meta.
			update_term_meta( $term_id, Term_Meta::ICON_META_KEY, esc_url_raw( $icon_url ) );
		}
	}

	/**
	 * Get count of migrated icons.
	 *
	 * @return int Number of series with icons.
	 */
	private function get_migrated_icons_count() {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE meta_key = %s AND meta_value != ''",
				Term_Meta::ICON_META_KEY
			)
		);
	}

	/**
	 * Display migration notice in admin.
	 */
	public function migration_notice() {
		// Check if notice was dismissed.
		if ( get_option( self::NOTICE_DISMISSED_OPTION ) ) {
			return;
		}

		$migration_data = get_option( self::MIGRATION_OPTION );

		// Only show for recent migrations from legacy plugin.
		if ( ! $migration_data || 'fresh_install' === $migration_data['source'] ) {
			return;
		}

		// Only show for first 7 days after migration.
		if ( time() - $migration_data['timestamp'] > WEEK_IN_SECONDS ) {
			update_option( self::NOTICE_DISMISSED_OPTION, true );
			return;
		}

		// Handle dismissal.
		if ( isset( $_GET['content-series-dismiss-notice'] ) && current_user_can( 'manage_options' ) ) {
			check_admin_referer( 'content_series_dismiss_notice' );
			update_option( self::NOTICE_DISMISSED_OPTION, true );
			wp_safe_redirect( remove_query_arg( array( 'content-series-dismiss-notice', '_wpnonce' ) ) );
			exit;
		}

		$dismiss_url = wp_nonce_url(
			add_query_arg( 'content-series-dismiss-notice', '1' ),
			'content_series_dismiss_notice'
		);

		$icons_count = isset( $migration_data['icons_count'] ) ? $migration_data['icons_count'] : 0;

		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Content Series', 'content-series' ); ?>:</strong>
				<?php
				printf(
					/* translators: %d: number of series icons migrated */
					esc_html__( 'Successfully migrated from PublishPress Series. %d series icon(s) were imported. Your existing series and post assignments have been preserved.', 'content-series' ),
					$icons_count
				);
				?>
			</p>
			<p>
				<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Dismiss', 'content-series' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Check if migration has been completed.
	 *
	 * @return bool True if migrated, false otherwise.
	 */
	public static function is_migrated() {
		return (bool) get_option( self::MIGRATION_OPTION );
	}

	/**
	 * Get migration data.
	 *
	 * @return array|false Migration data or false if not migrated.
	 */
	public static function get_migration_data() {
		return get_option( self::MIGRATION_OPTION );
	}
}
