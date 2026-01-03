<?php
/**
 * Term meta registration for series.
 *
 * @package ContentSeries
 */

namespace Content_Series;

/**
 * Handles term meta registration for series icons and additional data.
 */
class Term_Meta {

	/**
	 * Meta key for series icon.
	 */
	const ICON_META_KEY = 'series_icon';

	/**
	 * Meta key for series icon attachment ID.
	 */
	const ICON_ID_META_KEY = 'series_icon_id';

	/**
	 * Initialize term meta hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_meta' ) );

		// Add icon field to term edit forms.
		add_action( 'series_add_form_fields', array( $this, 'add_icon_field' ) );
		add_action( 'series_edit_form_fields', array( $this, 'edit_icon_field' ) );
		add_action( 'created_series', array( $this, 'save_icon' ) );
		add_action( 'edited_series', array( $this, 'save_icon' ) );

		// Add icon column to term list.
		add_filter( 'manage_edit-series_columns', array( $this, 'add_icon_column' ) );
		add_filter( 'manage_series_custom_column', array( $this, 'icon_column_content' ), 10, 3 );

		// Enqueue media scripts on term edit pages.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Register term meta fields.
	 */
	public function register_meta() {
		// Series icon URL.
		register_term_meta(
			CONTENT_SERIES_TAXONOMY,
			self::ICON_META_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);

		// Series icon attachment ID.
		register_term_meta(
			CONTENT_SERIES_TAXONOMY,
			self::ICON_ID_META_KEY,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);
	}

	/**
	 * Add icon field to add term form.
	 */
	public function add_icon_field() {
		?>
		<div class="form-field">
			<label for="series_icon"><?php esc_html_e( 'Series Icon', 'content-series' ); ?></label>
			<div class="series-icon-field-wrapper">
				<input type="hidden" name="series_icon_id" id="series_icon_id" value="">
				<input type="text" name="series_icon" id="series_icon" value="" class="regular-text" placeholder="<?php esc_attr_e( 'Icon URL', 'content-series' ); ?>">
				<button type="button" class="button series-icon-upload"><?php esc_html_e( 'Select Image', 'content-series' ); ?></button>
				<button type="button" class="button series-icon-remove" style="display:none;"><?php esc_html_e( 'Remove', 'content-series' ); ?></button>
			</div>
			<div class="series-icon-preview" style="margin-top: 10px;"></div>
			<p class="description"><?php esc_html_e( 'An icon or image representing this series.', 'content-series' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add icon field to edit term form.
	 *
	 * @param WP_Term $term Term object.
	 */
	public function edit_icon_field( $term ) {
		$icon_url = get_term_meta( $term->term_id, self::ICON_META_KEY, true );
		$icon_id  = get_term_meta( $term->term_id, self::ICON_ID_META_KEY, true );
		?>
		<tr class="form-field">
			<th scope="row">
				<label for="series_icon"><?php esc_html_e( 'Series Icon', 'content-series' ); ?></label>
			</th>
			<td>
				<div class="series-icon-field-wrapper">
					<input type="hidden" name="series_icon_id" id="series_icon_id" value="<?php echo esc_attr( $icon_id ); ?>">
					<input type="text" name="series_icon" id="series_icon" value="<?php echo esc_url( $icon_url ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Icon URL', 'content-series' ); ?>">
					<button type="button" class="button series-icon-upload"><?php esc_html_e( 'Select Image', 'content-series' ); ?></button>
					<button type="button" class="button series-icon-remove" <?php echo empty( $icon_url ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remove', 'content-series' ); ?></button>
				</div>
				<div class="series-icon-preview" style="margin-top: 10px;">
					<?php if ( $icon_url ) : ?>
						<img src="<?php echo esc_url( $icon_url ); ?>" alt="" style="max-width: 150px; max-height: 150px;">
					<?php endif; ?>
				</div>
				<p class="description"><?php esc_html_e( 'An icon or image representing this series.', 'content-series' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save icon meta when term is created/edited.
	 *
	 * @param int $term_id Term ID.
	 */
	public function save_icon( $term_id ) {
		// Verify nonce for term form submission.
		if ( isset( $_POST['_wpnonce'] ) ) {
			$nonce_action    = $term_id ? "update-tag_{$term_id}" : 'add-tag';
			$sanitized_nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );
			if ( ! wp_verify_nonce( $sanitized_nonce, $nonce_action ) ) {
				wp_die( esc_html__( 'Nonce is missing.', 'content-series' ) );
			}
		} elseif ( isset( $_POST['_wpnonce_add-tag'] ) ) {
			$sanitized_nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce_add-tag'] ) );
			// For created_series action, check add-tag nonce.
			if ( ! wp_verify_nonce( $sanitized_nonce, 'add-tag' ) ) {
				wp_die( esc_html__( 'Nonce is missing.', 'content-series' ) );
			}
		} else {
			wp_die( esc_html__( 'Nonce is missing.', 'content-series' ) );
		}

		if ( isset( $_POST['series_icon'] ) ) {
			$icon_url = sanitize_url( wp_unslash( $_POST['series_icon'] ) );
			update_term_meta( $term_id, self::ICON_META_KEY, $icon_url );
		}

		if ( isset( $_POST['series_icon_id'] ) ) {
			$icon_id = absint( $_POST['series_icon_id'] );
			update_term_meta( $term_id, self::ICON_ID_META_KEY, $icon_id );
		}
	}

	/**
	 * Add icon column to term list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_icon_column( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			if ( 'name' === $key ) {
				$new_columns['series_icon'] = __( 'Icon', 'content-series' );
			}
			$new_columns[ $key ] = $value;
		}

		return $new_columns;
	}

	/**
	 * Output icon column content.
	 *
	 * @param string $content     Column content.
	 * @param string $column_name Column name.
	 * @param int    $term_id     Term ID.
	 * @return string Modified content.
	 */
	public function icon_column_content( $content, $column_name, $term_id ) {
		if ( 'series_icon' !== $column_name ) {
			return $content;
		}

		$icon_url = get_term_meta( $term_id, self::ICON_META_KEY, true );

		if ( $icon_url ) {
			return sprintf(
				'<img src="%s" alt="" style="max-width: 40px; max-height: 40px; vertical-align: middle;">',
				esc_url( $icon_url )
			);
		}

		return '&mdash;';
	}

	/**
	 * Enqueue admin scripts for media uploader.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'series' !== $screen->taxonomy ) {
			return;
		}

		wp_enqueue_media();

		wp_add_inline_script(
			'media-editor',
			$this->get_media_script()
		);
	}

	/**
	 * Get inline JavaScript for media uploader.
	 *
	 * @return string JavaScript code.
	 */
	private function get_media_script() {
		return "
		jQuery(document).ready(function($) {
			var mediaFrame;

			$('.series-icon-upload').on('click', function(e) {
				e.preventDefault();

				if (mediaFrame) {
					mediaFrame.open();
					return;
				}

				mediaFrame = wp.media({
					title: '" . esc_js( __( 'Select Series Icon', 'content-series' ) ) . "',
					button: { text: '" . esc_js( __( 'Use as Icon', 'content-series' ) ) . "' },
					multiple: false
				});

				mediaFrame.on('select', function() {
					var attachment = mediaFrame.state().get('selection').first().toJSON();
					$('#series_icon').val(attachment.url);
					$('#series_icon_id').val(attachment.id);
					$('.series-icon-preview').html('<img src=\"' + attachment.url + '\" style=\"max-width: 150px; max-height: 150px;\">');
					$('.series-icon-remove').show();
				});

				mediaFrame.open();
			});

			$('.series-icon-remove').on('click', function(e) {
				e.preventDefault();
				$('#series_icon').val('');
				$('#series_icon_id').val('');
				$('.series-icon-preview').html('');
				$(this).hide();
			});
		});
		";
	}

	/**
	 * Get the icon URL for a series.
	 *
	 * @param int $term_id Series term ID.
	 * @return string|false Icon URL or false if not set.
	 */
	public static function get_series_icon( $term_id ) {
		$icon_url = get_term_meta( $term_id, self::ICON_META_KEY, true );
		return $icon_url ? $icon_url : false;
	}
}
