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
	 * Track if Quick Edit fields have been added to avoid duplicates.
	 *
	 * @var bool
	 */
	private static $quick_edit_fields_added = false;

	/**
	 * Initialize admin hooks.
	 */
	public function init() {
		add_shortcode( 'content_series_catalog', array( $this, 'render_catalog_shortcode' ) );

		// Quick Edit functionality for series parts.
		add_action( 'quick_edit_custom_box', array( $this, 'add_quick_edit_fields' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_quick_edit_series_parts' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'enqueue_quick_edit_scripts' ) );
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
	 * Render the series catalog shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_catalog_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'columns'    => 3,
				'show_count' => true,
				'show_icon'  => true,
			),
			$atts,
			'content_series_catalog'
		);

		$terms = get_terms(
			array(
				'taxonomy'   => CONTENT_SERIES_TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '<p>' . esc_html__( 'No series found.', 'content-series' ) . '</p>';
		}

		$columns = absint( $atts['columns'] );
		$output  = '<div class="content-series-catalog" style="display: grid; grid-template-columns: repeat(' . $columns . ', 1fr); gap: 2rem;">';

		foreach ( $terms as $term ) {
			$icon_url = Term_Meta::get_series_icon( $term->term_id );
			$link     = get_term_link( $term );

			$output .= '<div class="content-series-catalog__item" style="text-align: center;">';

			if ( $atts['show_icon'] && $icon_url ) {
				$output .= sprintf(
					'<a href="%s"><img src="%s" alt="%s" style="max-width: 150px; max-height: 150px; margin-bottom: 1rem;"></a>',
					esc_url( $link ),
					esc_url( $icon_url ),
					esc_attr( $term->name )
				);
			}

			$output .= sprintf(
				'<h3 style="margin: 0 0 0.5rem;"><a href="%s">%s</a></h3>',
				esc_url( $link ),
				esc_html( $term->name )
			);

			if ( $atts['show_count'] ) {
				$output .= sprintf(
					'<p style="margin: 0; color: #666;">%s</p>',
					/* translators: %d: number of posts */
					esc_html( sprintf( _n( '%d post', '%d posts', $term->count, 'content-series' ), $term->count ) )
				);
			}

			if ( $term->description ) {
				$output .= sprintf(
					'<p style="margin: 0.5rem 0 0; font-size: 0.9em;">%s</p>',
					esc_html( wp_trim_words( $term->description, 20 ) )
				);
			}

			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
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
		// The column name for taxonomies is typically the taxonomy slug, but may be prefixed.
		// Check for both the taxonomy slug and common variations.
		$series_column_names = array( CONTENT_SERIES_TAXONOMY, 'my-series', 'taxonomy-' . CONTENT_SERIES_TAXONOMY );
		if ( ! in_array( $column_name, $series_column_names, true ) ) {
			return;
		}

		// Only add once (use static flag to avoid duplicates).
		if ( self::$quick_edit_fields_added ) {
			return;
		}

		self::$quick_edit_fields_added = true;

		// Get all series terms.
		$series_terms = get_terms(
			array(
				'taxonomy'   => CONTENT_SERIES_TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( empty( $series_terms ) || is_wp_error( $series_terms ) ) {
			return;
		}

		// Get post counts for each series.
		$series_counts = array();
		foreach ( $series_terms as $term ) {
			$series_counts[ $term->term_id ] = $term->count;
		}

		?>
		<div class="content-series-parts-container" id="content-series-parts-container" style="display: none;">
			<div class="content-series-quick-edit-parts">
				<?php foreach ( $series_terms as $term ) : ?>
					<?php $total_parts = isset( $series_counts[ $term->term_id ] ) ? $series_counts[ $term->term_id ] : 0; ?>
					<div class="content-series-part-field" data-series-id="<?php echo esc_attr( $term->term_id ); ?>" data-total-parts="<?php echo esc_attr( $total_parts ); ?>" style="display: none;">
						<label>
							<span class="series-name"><?php echo esc_html( $term->name ); ?></span>
							<span class="part-label"> - Part</span>
							<input 
								type="number" 
								name="series_part_<?php echo esc_attr( $term->term_id ); ?>" 
								value="" 
								min="1" 
								class="content-series-part-input"
								data-series-id="<?php echo esc_attr( $term->term_id ); ?>"
							/>
							<span class="total-parts">of <?php echo esc_html( $total_parts ); ?></span>
						</label>
					</div>
				<?php endforeach; ?>
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
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['action'] ) || 'inline-save' !== $_POST['action'] ) {
			return;
		}

		// Get all series terms to check which parts were submitted.
		$series_terms = get_terms(
			array(
				'taxonomy'   => CONTENT_SERIES_TAXONOMY,
				'hide_empty' => false,
			)
		);

		if ( empty( $series_terms ) || is_wp_error( $series_terms ) ) {
			return;
		}

		// Save series part values.
		foreach ( $series_terms as $term ) {
			$input_name = 'series_part_' . $term->term_id;
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST[ $input_name ] ) ) {
				$part_value = sanitize_text_field( wp_unslash( $_POST[ $input_name ] ) );
				$part_value = absint( $part_value );
				
				// Only update if value is valid (>= 1).
				if ( $part_value >= 1 ) {
					Post_Meta::set_post_series_part( $post_id, $term->term_id, $part_value );
				}
			}
		}
	}

	/**
	 * Enqueue JavaScript for Quick Edit functionality.
	 */
	public function enqueue_quick_edit_scripts() {
		$screen = get_current_screen();
		// Check for both possible screen IDs.
		if ( ! $screen || ( 'edit-post' !== $screen->id && 'post' !== $screen->id ) ) {
			return;
		}

		// Get all series terms for JavaScript.
		$series_terms = get_terms(
			array(
				'taxonomy'   => CONTENT_SERIES_TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		$series_data = array();
		if ( ! empty( $series_terms ) && ! is_wp_error( $series_terms ) ) {
			foreach ( $series_terms as $term ) {
				$series_data[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
				);
			}
		}

		// Get all posts with their series assignments and part numbers.
		$posts_data = array();
		$posts      = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		foreach ( $posts as $post ) {
			$series = get_the_terms( $post->ID, CONTENT_SERIES_TAXONOMY );
			if ( ! $series || is_wp_error( $series ) ) {
				continue;
			}

			$post_series = array();
			foreach ( $series as $term ) {
				$part = Post_Meta::get_post_series_part( $post->ID, $term->term_id );
				$post_series[ $term->term_id ] = $part;
			}

			if ( ! empty( $post_series ) ) {
				$posts_data[ $post->ID ] = $post_series;
			}
		}

		?>
		<script type="text/javascript">
		(function() {
			var contentSeriesData = {
				series: <?php echo wp_json_encode( $series_data ); ?>,
				posts: <?php echo wp_json_encode( $posts_data ); ?>
			};

			// Function to update series part fields visibility and values.
			function updateSeriesPartFields(row, postId) {
				if (!row) {
					return;
				}

				// Get series assignments for this post from the taxonomy inputs.
				// WordPress uses different formats for hierarchical vs non-hierarchical taxonomies.
				var selectedSeries = [];
				
				// Try hierarchical format first (checkboxes).
				var seriesCheckboxes = row.querySelectorAll('input[name="tax_input[series][]"]:checked');
				if (seriesCheckboxes.length > 0) {
					seriesCheckboxes.forEach(function(checkbox) {
						var seriesId = parseInt(checkbox.value, 10);
						if (!isNaN(seriesId) && selectedSeries.indexOf(seriesId) === -1) {
							selectedSeries.push(seriesId);
						}
					});
				}
				
				// If no checkboxes found, try other formats.
				if (selectedSeries.length === 0) {
					// Check for hidden inputs that WordPress might use.
					var hiddenInputs = row.querySelectorAll('input[type="hidden"][name*="series"]');
					hiddenInputs.forEach(function(input) {
						var value = input.value.trim();
						if (value) {
							var seriesId = parseInt(value, 10);
							if (!isNaN(seriesId) && selectedSeries.indexOf(seriesId) === -1) {
								selectedSeries.push(seriesId);
							}
						}
					});
					
					// Try text input format for non-hierarchical taxonomies.
					if (selectedSeries.length === 0) {
						var possibleInputs = [
							'input[name="tax_input[series]"]',
							'input.tax_input_series',
							'input[data-wp-taxonomy="series"]'
						];
						
						var seriesInput = null;
						for (var i = 0; i < possibleInputs.length; i++) {
							seriesInput = row.querySelector(possibleInputs[i]);
							if (seriesInput) {
								break;
							}
						}
						
						if (seriesInput && seriesInput.value) {
							// Parse comma-separated term names or IDs.
							var values = seriesInput.value.split(',').map(function(v) { return v.trim(); });
							// Try to match with series data to get IDs.
							values.forEach(function(value) {
								if (!value) {
									return;
								}
								// First try to find by name (case-insensitive).
								var series = contentSeriesData.series.find(function(s) {
									return s.name.toLowerCase() === value.toLowerCase();
								});
								// If not found by name, try by ID.
								if (!series) {
									var valueAsInt = parseInt(value, 10);
									if (!isNaN(valueAsInt)) {
										series = contentSeriesData.series.find(function(s) {
											return s.id === valueAsInt;
										});
									}
								}
								if (series && selectedSeries.indexOf(series.id) === -1) {
									selectedSeries.push(series.id);
								}
							});
						}
					}
				}

				// If no series detected from form, fall back to post data.
				if (selectedSeries.length === 0 && postId && contentSeriesData.posts[postId]) {
					selectedSeries = Object.keys(contentSeriesData.posts[postId]).map(function(id) {
						return parseInt(id, 10);
					});
				}

				// Show/hide and populate series part fields.
				var partsContainer = row.querySelector('#content-series-parts-container');
				if (partsContainer && selectedSeries.length > 0) {
					partsContainer.style.display = '';
				} else if (partsContainer) {
					partsContainer.style.display = 'none';
				}

				contentSeriesData.series.forEach(function(series) {
					var field = row.querySelector('.content-series-part-field[data-series-id="' + series.id + '"]');
					if (!field) {
						return;
					}

					var input = field.querySelector('input.content-series-part-input[data-series-id="' + series.id + '"]');
					var isSelected = selectedSeries.indexOf(series.id) !== -1;
					
					if (isSelected) {
						// Show field and populate with current value.
						field.style.display = 'block';
						field.classList.add('show');
						var currentPart = 1;
						if (postId && contentSeriesData.posts[postId] && contentSeriesData.posts[postId][series.id]) {
							currentPart = contentSeriesData.posts[postId][series.id];
						}
						if (input) {
							input.value = currentPart;
						}
					} else {
						// Hide field.
						field.style.display = 'none';
						field.classList.remove('show');
						if (input) {
							input.value = '';
						}
					}
				});
			}

			// Function to position Series Parts container after the Series taxonomy howto paragraph.
			function positionSeriesPartsContainer(row) {
				if (!row) {
					return false;
				}

				var partsContainer = row.querySelector('#content-series-parts-container');
				if (!partsContainer) {
					return false;
				}

				// Find the Series taxonomy howto paragraph using the ID pattern from WordPress core.
				var seriesHowto = row.querySelector('#inline-edit-series-desc');
				if (!seriesHowto) {
					return false;
				}

				// Check if already in correct position (right after the howto paragraph).
				if (seriesHowto.nextElementSibling === partsContainer) {
					return true;
				}

				// Find the parent container (inline-edit-tags-wrap).
				var tagsWrap = seriesHowto.closest('.inline-edit-tags-wrap');
				if (!tagsWrap) {
					return false;
				}

				// Remove from current location if it has a parent.
				if (partsContainer.parentNode) {
					partsContainer.parentNode.removeChild(partsContainer);
				}

				// Insert right after the howto paragraph.
				tagsWrap.insertBefore(partsContainer, seriesHowto.nextSibling);
				partsContainer.style.display = '';
				return true;
			}

			// Extend inlineEditPost.edit to populate series part fields.
			var wpInlineEdit = inlineEditPost.edit;
			inlineEditPost.edit = function(id) {
				wpInlineEdit.apply(this, arguments);

				var post_id = 0;
				if (typeof(id) === 'object') {
					post_id = parseInt(this.getId(id), 10);
				}

				if (post_id > 0) {
					var row = document.getElementById('edit-' + post_id);
					if (row) {
						// Position the container and update fields.
						positionSeriesPartsContainer(row);
						updateSeriesPartFields(row, post_id);
						
						// Try again after a short delay in case DOM isn't fully ready.
						setTimeout(function() {
							var currentRow = document.getElementById('edit-' + post_id);
							if (currentRow) {
								positionSeriesPartsContainer(currentRow);
								updateSeriesPartFields(currentRow, post_id);
							}
						}, 100);
					}
				}
			};

			// Update series part fields when series selection changes.
			// Listen for changes on the series textarea.
			document.addEventListener('input', function(event) {
				var target = event.target;
				if (target && target.matches && target.matches('#the-list .inline-edit-row textarea.tax_input_series, #the-list .inline-edit-row textarea[name="tax_input[series]"]')) {
					var row = target.closest('.inline-edit-row');
					if (row) {
						var postId = 0;
						var postIdMatch = row.id.match(/^edit-(\d+)$/);
						if (postIdMatch) {
							postId = parseInt(postIdMatch[1], 10);
						}
						updateSeriesPartFields(row, postId);
					}
				}
			});
		})();
		</script>
		<style type="text/css">
		.inline-edit-tags-wrap:has(#inline-edit-series-desc) {
			background-color: #f0f0f1;
			border-radius: 3px;
			padding: 10px;
			margin-top: 5px;
		}
		.content-series-parts-container {
			margin-top: 10px;
		}
		.content-series-quick-edit-parts {
			margin-left: 0;
		}
		.content-series-part-field {
			margin-bottom: 8px;
			display: none;
		}
		.content-series-part-field.show {
			display: block !important;
		}
		.content-series-part-field:last-child {
			margin-bottom: 0;
		}
		.content-series-part-field label {
			display: block;
			margin-bottom: 4px;
			font-weight: normal;
		}
		.content-series-part-field .series-name {
			display: inline-block;
			font-weight: 600;
			margin-right: 4px;
		}
		.content-series-part-field .part-label {
			display: inline-block;
			margin-right: 4px;
		}
		.content-series-part-field input[type="number"] {
			width: 60px;
			margin: 0 4px;
		}
		.content-series-part-field .total-parts {
			display: inline-block;
			margin-left: 4px;
		}
		</style>
		<?php
	}
}
