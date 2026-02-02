<?php
/**
 * Admin functionality for Content Series.
 *
 * @package ContentSeries
 */

namespace Content_Series;

/**
 * Handles admin menu and admin-specific functionality.
 */
class Admin {

	/**
	 * Initialize admin hooks.
	 */
	public function init() {
		// Quick Edit functionality for series parts.
		add_action( 'quick_edit_custom_box', array( $this, 'add_quick_edit_fields' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_quick_edit_series_parts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_quick_edit_scripts' ) );

		// Add inline data to post rows for Quick Edit.
		add_action( 'add_inline_data', array( $this, 'add_inline_series_data' ), 10, 2 );
	}

	/**
	 * Redirect main menu page to taxonomy page.
	 */
	public function redirect_to_taxonomy() {
		// Check capabilities.
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'content-series' ) );
		}

		wp_safe_redirect( admin_url( 'edit-tags.php?taxonomy=series' ) );
		exit;
	}

	/**
	 * Add inline series data to post rows for Quick Edit JavaScript.
	 * Adds data to the existing inline data container that WordPress creates.
	 *
	 * @param WP_Post      $post             The current post object.
	 * @param WP_Post_Type $post_type_object The current post's post type object.
	 */
	public function add_inline_series_data( $post, $post_type_object ) {
		// Only add to posts.
		if ( 'post' !== $post->post_type ) {
			return;
		}

		// Get series assignments and part numbers for this post.
		$series = get_the_terms( $post->ID, CONTENT_SERIES_TAXONOMY );
		if ( ! $series || is_wp_error( $series ) ) {
			return;
		}

		$series_data = array();
		foreach ( $series as $term ) {
			$part                          = Post_Meta::get_post_series_part( $post->ID, $term->term_id );
			$series_data[ $term->term_id ] = array(
				'id'    => $term->term_id,
				'name'  => $term->name,
				'count' => $term->count,
				'part'  => $part,
			);
		}

		if ( ! empty( $series_data ) ) {
			// WordPress creates #inline_{post_id} container via get_inline_data().
			// Add our data as a hidden div within that container.
			$data = wp_json_encode( $series_data );
			?>
			<div class="hidden" data-series-data="<?php echo esc_attr( $data ); ?>"></div>
			<?php
		}
	}

	/**
	 * Add series part fields to Quick Edit form.
	 *
	 * @param string $column_name Column name.
	 * @param string $post_type   Post type.
	 */
	public function add_quick_edit_fields( $column_name, $post_type ) {
		// Only add to posts.
		if ( 'post' !== $post_type ) {
			return;
		}

		// Only add fields when the series taxonomy column is being processed.
		// The column name for taxonomies is typically the taxonomy slug, but may be prefixed or custom.
		// Check for:
		// 1. Direct taxonomy slug ('series')
		// 2. Legacy/custom column name ('my-series' - if column was customized)
		// 3. Prefixed taxonomy slug ('taxonomy-series').
		$series_column_names = array( CONTENT_SERIES_TAXONOMY, 'my-series', 'taxonomy-' . CONTENT_SERIES_TAXONOMY );
		if ( ! in_array( $column_name, $series_column_names, true ) ) {
			return;
		}

		?>
		<div class="content-series-parts-container" id="content-series-parts-container" style="display: none;">
			<div class="content-series-quick-edit-parts" id="content-series-quick-edit-parts">
				<!-- Fields will be dynamically created by JavaScript -->
			</div>
		</div>
		<?php
	}

	/**
	 * Save series part values from Quick Edit.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_quick_edit_series_parts( $post_id ) {
		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if this is a post.
		if ( 'post' !== get_post_type( $post_id ) ) {
			return;
		}

		// Check if this is from Quick Edit (inline-save action).
		if ( ! isset( $_POST['action'] ) || 'inline-save' !== $_POST['action'] ) {
			return;
		}

		// Verify nonce for Quick Edit.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce() validates the nonce value.
		if ( ! isset( $_POST['_inline_edit'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_inline_edit'] ), 'inlineeditnonce' ) ) {
			return;
		}

		// Parse POST data to find which series IDs have submitted part values.
		$series_ids_with_data = array();
		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'series_part_' ) === 0 ) {
				$series_id = absint( str_replace( 'series_part_', '', $key ) );
				if ( $series_id > 0 ) {
					$series_ids_with_data[ $series_id ] = $value;
				}
			}
		}

		// Early return if no series part data submitted.
		if ( empty( $series_ids_with_data ) ) {
			return;
		}

		// Get series terms assigned to this post.
		$assigned_series = get_the_terms( $post_id, CONTENT_SERIES_TAXONOMY );
		if ( empty( $assigned_series ) || is_wp_error( $assigned_series ) ) {
			return;
		}

		$assigned_ids = wp_list_pluck( $assigned_series, 'term_id' );

		// Save part values only for series the post is actually assigned to.
		foreach ( $assigned_ids as $series_id ) {
			if ( isset( $series_ids_with_data[ $series_id ] ) ) {
				$part_value = sanitize_text_field( wp_unslash( $series_ids_with_data[ $series_id ] ) );
				$part_value = absint( $part_value );
				// Only update if value is valid (>= 1).
				if ( $part_value >= 1 ) {
					Post_Meta::set_post_series_part( $post_id, $series_id, $part_value );
				}
			}
		}
	}

	/**
	 * Enqueue JavaScript for Quick Edit functionality.
	 */
	public function enqueue_quick_edit_scripts() {
		$screen = get_current_screen();
		// Only load on the post list table page (edit.php).
		if ( ! $screen || 'edit-post' !== $screen->id ) {
			return;
		}

		// Get REST API base URL for fetching series data.
		$rest_url = rest_url( 'wp/v2/' );

		// Enqueue JavaScript file.
		wp_enqueue_script(
			'content-series-quick-edit',
			CONTENT_SERIES_URL . 'assets/js/quick-edit-series-parts.js',
			array( 'inline-edit-post' ),
			CONTENT_SERIES_VERSION,
			true
		);

		// Add inline script to pass PHP data to JavaScript.
		$inline_script = sprintf(
			'var contentSeriesQuickEditData = %s;',
			wp_json_encode( array( 'restUrl' => $rest_url ) )
		);
		wp_add_inline_script( 'content-series-quick-edit', $inline_script, 'before' );

		// Enqueue CSS file.
		wp_enqueue_style(
			'content-series-quick-edit',
			CONTENT_SERIES_URL . 'assets/css/quick-edit-series-parts.css',
			array(),
			CONTENT_SERIES_VERSION
		);
	}
}
