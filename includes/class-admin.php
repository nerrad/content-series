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
		add_shortcode( 'content_series_catalog', array( $this, 'render_catalog_shortcode' ) );

		// Quick Edit functionality for series parts.
		add_action( 'quick_edit_custom_box', array( $this, 'add_quick_edit_fields' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_quick_edit_series_parts' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'enqueue_quick_edit_scripts' ) );
		
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
			$part = Post_Meta::get_post_series_part( $post->ID, $term->term_id );
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
		// The column name for taxonomies is typically the taxonomy slug, but may be prefixed.
		// Check for both the taxonomy slug and common variations.
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

		// Get REST API base URL for fetching series data.
		$rest_url = rest_url( 'wp/v2/' );

		?>
		<script type="text/javascript">
		(function() {
			var restUrl = <?php echo wp_json_encode( $rest_url ); ?>;
			var seriesCache = {}; // Cache for series data (id, name, count)

			// Function to get series data - first from inline data, then from API if needed.
			function getSeriesData(seriesName, callback) {
				// Check cache first.
				var cached = Object.values(seriesCache).find(function(s) {
					return s.name.toLowerCase() === seriesName.toLowerCase();
				});
				if (cached) {
					callback(cached);
					return;
				}

				// Check if we can find it in any inline data on the page.
				var inlineDataElements = document.querySelectorAll('[data-series-data]');
				for (var i = 0; i < inlineDataElements.length; i++) {
					try {
						var data = JSON.parse(inlineDataElements[i].getAttribute('data-series-data'));
						var series = Object.values(data).find(function(s) {
							return s.name.toLowerCase() === seriesName.toLowerCase();
						});
						if (series) {
							// Normalize the series object (ensure it has all needed properties).
							var normalizedSeries = {
								id: parseInt(series.id, 10),
								name: series.name,
								count: parseInt(series.count, 10) || 0
							};
							seriesCache[normalizedSeries.id] = normalizedSeries;
							callback(normalizedSeries);
							return;
						}
					} catch (e) {
						// Ignore parse errors.
					}
				}

				// Not found in inline data, fetch from API.
				fetch(restUrl + 'series?search=' + encodeURIComponent(seriesName) + '&per_page=1')
					.then(function(response) {
						return response.json();
					})
					.then(function(data) {
						if (data && data.length > 0) {
							var series = {
								id: data[0].id,
								name: data[0].name,
								count: data[0].count || 0
							};
							seriesCache[series.id] = series;
							callback(series);
						} else {
							callback(null);
						}
					})
					.catch(function(error) {
						console.error('Error fetching series data:', error);
						callback(null);
					});
			}

			// Function to get current part numbers for a post from inline data.
			function getCurrentParts(postId, callback) {
				if (!postId) {
					callback({});
					return;
				}

				// Get from the inline data container (WordPress creates #inline_{postId}).
				var inlineContainer = document.getElementById('inline_' + postId);
				if (!inlineContainer) {
					callback({});
					return;
				}

				// Find the series data element within the inline container.
				var seriesDataElement = inlineContainer.querySelector('[data-series-data]');
				if (!seriesDataElement) {
					callback({});
					return;
				}

				try {
					var seriesData = JSON.parse(seriesDataElement.getAttribute('data-series-data'));
					var parts = {};
					Object.keys(seriesData).forEach(function(seriesId) {
						parts[parseInt(seriesId, 10)] = parseInt(seriesData[seriesId].part, 10);
					});
					callback(parts);
				} catch (e) {
					console.error('Error parsing series data:', e);
					callback({});
				}
			}

			// Function to create a series part field.
			function createSeriesPartField(series, currentPart) {
				var field = document.createElement('div');
				field.className = 'content-series-part-field show';
				field.setAttribute('data-series-id', series.id);
				field.setAttribute('data-total-parts', series.count);

				var label = document.createElement('label');
				label.innerHTML = 
					'<span class="series-name">' + series.name + '</span>' +
					'<span class="part-label"> - Part</span>' +
					'<input type="number" name="series_part_' + series.id + '" value="' + currentPart + '" min="1" class="content-series-part-input" data-series-id="' + series.id + '" />' +
					'<span class="total-parts">of ' + series.count + '</span>';

				field.appendChild(label);
				return field;
			}

			// Function to update series part fields - creates fields dynamically.
			function updateSeriesPartFields(row, postId) {
				if (!row) {
					return;
				}

				var partsContainer = row.querySelector('#content-series-parts-container');
				var partsWrapper = row.querySelector('#content-series-quick-edit-parts');
				if (!partsContainer || !partsWrapper) {
					return;
				}

				// Get series assignments from the textarea.
				var seriesTextarea = row.querySelector('textarea.tax_input_series, textarea[name="tax_input[series]"]');
				if (!seriesTextarea || !seriesTextarea.value) {
					partsContainer.style.display = 'none';
					partsWrapper.innerHTML = '';
					return;
				}

				// Parse comma-separated term names.
				var seriesNames = seriesTextarea.value.split(',').map(function(v) { return v.trim(); }).filter(function(v) { return v; });
				
				if (seriesNames.length === 0) {
					partsContainer.style.display = 'none';
					partsWrapper.innerHTML = '';
					return;
				}

				// Clear existing fields.
				partsWrapper.innerHTML = '';

				// Fetch series data and create fields.
				var pendingRequests = seriesNames.length;
				var allSeries = [];

				// First, fetch all series data.
				seriesNames.forEach(function(seriesName) {
					getSeriesData(seriesName, function(series) {
						if (series) {
							allSeries.push(series);
						}
						pendingRequests--;

						// When all series are fetched, get part numbers and create fields.
						if (pendingRequests === 0) {
							if (allSeries.length === 0) {
								partsContainer.style.display = 'none';
								return;
							}

				// Get all part numbers for this post from inline data.
				getCurrentParts(postId, function(parts) {
					allSeries.forEach(function(series) {
						var currentPart = parts[series.id] || 1;
						var field = createSeriesPartField(series, currentPart);
						partsWrapper.appendChild(field);
					});

					if (allSeries.length > 0) {
						partsContainer.style.display = '';
					} else {
						partsContainer.style.display = 'none';
					}
				});
						}
					});
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
					}
				}
			};

			// Update series part fields when series selection changes.
			// Listen for changes on the series textarea.
			var updateTimeout;
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
						// Debounce to avoid too many API calls while typing.
						clearTimeout(updateTimeout);
						updateTimeout = setTimeout(function() {
							updateSeriesPartFields(row, postId);
						}, 300);
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
