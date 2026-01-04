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
			var seriesCache = {}; // Cache for series data (id, name)
			var seriesCountCache = {}; // Canonical cache for total parts count per series (seriesId -> count)

			// Helper function to normalize series names for comparison.
			function normalizeSeriesName(name) {
				if (!name) return '';
				return name.toLowerCase().trim().replace(/\s+/g, ' ');
			}

			// Function to get series data - first from inline data, then from API if needed.
			function getSeriesData(seriesName, callback) {
				if (!seriesName || !seriesName.trim()) {
					callback(null);
					return;
				}

				var normalizedSearchName = normalizeSeriesName(seriesName);

				// Check cache first.
				var cached = Object.values(seriesCache).find(function(s) {
					return normalizeSeriesName(s.name) === normalizedSearchName;
				});
				if (cached) {
					// Ensure cached object has count from seriesCountCache.
					if (cached.count === undefined || cached.count === null) {
						cached.count = seriesCountCache[cached.id] !== undefined ? seriesCountCache[cached.id] : 0;
					}
					callback(cached);
					return;
				}

				// Check if we can find it in any inline data on the page.
				var inlineDataElements = document.querySelectorAll('[data-series-data]');
				for (var i = 0; i < inlineDataElements.length; i++) {
					try {
						var data = JSON.parse(inlineDataElements[i].getAttribute('data-series-data'));
						var series = Object.values(data).find(function(s) {
							return normalizeSeriesName(s.name) === normalizedSearchName;
						});
						if (series) {
							// Normalize the series object (ensure it has all needed properties).
							var seriesId = parseInt(series.id, 10);
							var normalizedSeries = {
								id: seriesId,
								name: series.name
							};
							
							// Use cached count if available, otherwise use inline data count and cache it.
							if (seriesCountCache[seriesId] !== undefined) {
								normalizedSeries.count = seriesCountCache[seriesId];
							} else {
								// Get count from inline data, defaulting to 0 if missing.
								var count = 0;
								if (series.count !== undefined && series.count !== null) {
									count = parseInt(series.count, 10) || 0;
								}
								seriesCountCache[seriesId] = count;
								normalizedSeries.count = count;
							}
							
							seriesCache[seriesId] = normalizedSeries;
							callback(normalizedSeries);
							return;
						}
					} catch (e) {
						// Ignore parse errors.
					}
				}

				// Not found in inline data, fetch from API.
				// Search for series by name (WordPress REST API search does partial matching).
				var apiUrl = restUrl + 'series?search=' + encodeURIComponent(seriesName) + '&per_page=100';
				fetch(apiUrl)
					.then(function(response) {
						if (!response.ok) {
							throw new Error('API request failed: ' + response.status);
						}
						return response.json();
					})
					.then(function(data) {
						if (!data || !Array.isArray(data) || data.length === 0) {
							callback(null);
							return;
						}
						
						// Find exact match (case-insensitive, normalized) since search can return partial matches.
						var normalizedSearchName = normalizeSeriesName(seriesName);
						var exactMatch = data.find(function(item) {
							return item && item.name && normalizeSeriesName(item.name) === normalizedSearchName;
						});
						
						if (!exactMatch) {
							// No exact match found - series might not exist yet.
							callback(null);
							return;
						}
						
						var seriesId = parseInt(exactMatch.id, 10);
						var count = parseInt(exactMatch.count, 10) || 0;
						
						// API is authoritative - always use API count and update cache.
						seriesCountCache[seriesId] = count;
						
						var series = {
							id: seriesId,
							name: exactMatch.name,
							count: count
						};
						
						// Cache and return the series.
						seriesCache[seriesId] = series;
						callback(series);
					})
					.catch(function(error) {
						console.error('Error fetching series data for "' + seriesName + '":', error);
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
			function createSeriesPartField(series, currentPart, isNewAddition) {
				// Ensure count is defined (should always be set, but safety check).
				var count = (series.count !== undefined && series.count !== null) ? series.count : 0;
				var previewCount = isNewAddition ? count + 1 : null;
				
				var field = document.createElement('div');
				field.className = 'content-series-part-field show';
				field.setAttribute('data-series-id', series.id);
				field.setAttribute('data-total-parts', count);

				var label = document.createElement('label');
				var totalPartsText = 'of ' + count;
				if (previewCount !== null) {
					totalPartsText += ' ( -> ' + previewCount + ' )';
				}
				
				label.innerHTML = 
					'<span class="series-name">' + series.name + '</span>' +
					'<span class="part-label"> - Part</span>' +
					'<input type="number" name="series_part_' + series.id + '" value="' + currentPart + '" min="1" class="content-series-part-input" data-series-id="' + series.id + '" />' +
					'<span class="total-parts">' + totalPartsText + '</span>';

				field.appendChild(label);
				return field;
			}

			// Function to update series part fields - creates fields dynamically.
			// Use a request counter to track the current request batch and ignore stale callbacks.
			var updateRequestCounter = 0;
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

				// Parse comma-separated term names, removing empty strings and trimming.
				// Normalize whitespace to handle multiple spaces or tabs.
				var seriesNames = seriesTextarea.value.split(',').map(function(v) { 
					return v.trim().replace(/\s+/g, ' ');
				}).filter(function(v) { return v && v.length > 0; });
				
				if (seriesNames.length === 0) {
					partsContainer.style.display = 'none';
					partsWrapper.innerHTML = '';
					return;
				}

				// Increment request counter for this batch.
				updateRequestCounter++;
				var currentRequest = updateRequestCounter;

				// Clear existing fields immediately.
				partsWrapper.innerHTML = '';

				// Fetch series data and create fields.
				var pendingRequests = seriesNames.length;
				var allSeries = [];

				// First, fetch all series data.
				seriesNames.forEach(function(seriesName) {
					getSeriesData(seriesName, function(series) {
						// Ignore callbacks from stale requests.
						if (currentRequest !== updateRequestCounter) {
							return;
						}

						if (series) {
							// Ensure series has required properties.
							if (series.count === undefined || series.count === null) {
								console.warn('Series missing count:', series);
								series.count = seriesCountCache[series.id] || 0;
							}
							allSeries.push(series);
						} else {
							// Debug: log when series is not found.
							console.warn('Series not found: "' + seriesName + '"');
						}
						pendingRequests--;

						// When all series are fetched, get part numbers and create fields.
						if (pendingRequests === 0) {
							// Double-check this is still the current request.
							if (currentRequest !== updateRequestCounter) {
								return;
							}

							if (allSeries.length === 0) {
								partsContainer.style.display = 'none';
								return;
							}

							// Clear fields again before adding new ones (in case of race condition).
							partsWrapper.innerHTML = '';

							// Deduplicate series by ID (in case same series appears multiple times).
							var uniqueSeries = [];
							var seenIds = {};
							allSeries.forEach(function(series) {
								if (!seenIds[series.id]) {
									seenIds[series.id] = true;
									uniqueSeries.push(series);
								}
							});

							// Get all part numbers for this post from inline data.
							getCurrentParts(postId, function(parts) {
								// Final check for stale request.
								if (currentRequest !== updateRequestCounter) {
									return;
								}

								// Create fields for all unique series.
								uniqueSeries.forEach(function(series) {
									// Check if this post is being newly added to the series (not in parts means it's new).
									var isNewAddition = !parts[series.id];
									var currentPart;
									if (isNewAddition) {
										// When adding to a new series, default to the total parts it will become.
										currentPart = (series.count || 0) + 1;
									} else {
										// Use existing part number.
										currentPart = parts[series.id] || 1;
									}
									var field = createSeriesPartField(series, currentPart, isNewAddition);
									partsWrapper.appendChild(field);
								});

								if (uniqueSeries.length > 0) {
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

			// Track series changes when Quick Edit is saved.
			// Store the series before save to compare after save completes.
			var seriesBeforeSave = {};
			var wpInlineSave = inlineEditPost.save;
			inlineEditPost.save = function(id) {
				// Capture current series assignments before save.
				var post_id = 0;
				if (typeof(id) === 'object') {
					post_id = parseInt(this.getId(id), 10);
				} else {
					post_id = parseInt(id, 10);
				}

				if (post_id > 0) {
					// Get current series from inline data before save.
					var inlineContainer = document.getElementById('inline_' + post_id);
					if (inlineContainer) {
						var seriesDataElement = inlineContainer.querySelector('[data-series-data]');
						if (seriesDataElement) {
							try {
								var seriesData = JSON.parse(seriesDataElement.getAttribute('data-series-data'));
								seriesBeforeSave[post_id] = Object.keys(seriesData).map(function(id) {
									return parseInt(id, 10);
								});
							} catch (e) {
								seriesBeforeSave[post_id] = [];
							}
						} else {
							seriesBeforeSave[post_id] = [];
						}
					} else {
						seriesBeforeSave[post_id] = [];
					}
				}

				// Call original save function.
				wpInlineSave.apply(this, arguments);

				// Listen for AJAX completion to update counts after save.
				var ajaxCompleteHandler = function(event, xhr, settings) {
					// Check if this is the inline-save AJAX request.
					if (settings.data && settings.data.indexOf('action=inline-save') !== -1) {
						// Remove handler to prevent multiple triggers.
						if (typeof jQuery !== 'undefined') {
							jQuery(document).off('ajaxComplete', ajaxCompleteHandler);
						}

						// Wait a bit for DOM to update, then check for changes.
						setTimeout(function() {
							if (post_id > 0) {
								updateSeriesCountsAfterSave(post_id, seriesBeforeSave[post_id] || []);
								// Clean up.
								delete seriesBeforeSave[post_id];
							}
						}, 200);
					}
				};

				// Attach handler (using jQuery since WordPress uses it for AJAX).
				// If jQuery is not available, we'll assume success after a delay.
				if (typeof jQuery !== 'undefined') {
					jQuery(document).on('ajaxComplete', ajaxCompleteHandler);
				} else {
					// Fallback: assume save completed successfully after a delay.
					setTimeout(function() {
						if (post_id > 0) {
							updateSeriesCountsAfterSave(post_id, seriesBeforeSave[post_id] || []);
							delete seriesBeforeSave[post_id];
						}
					}, 1000);
				}
			};

			// Function to update series counts after a post is saved.
			// Only updates when posts are added/removed from series (not when part numbers change).
			function updateSeriesCountsAfterSave(postId, seriesBefore) {
				// Get the new series assignments from the updated post row.
				var postRow = document.getElementById('post-' + postId);
				var seriesAfter = [];

				// Try to get series from the updated inline data first.
				var inlineContainer = document.getElementById('inline_' + postId);
				if (inlineContainer) {
					var seriesDataElement = inlineContainer.querySelector('[data-series-data]');
					if (seriesDataElement) {
						try {
							var seriesData = JSON.parse(seriesDataElement.getAttribute('data-series-data'));
							seriesAfter = Object.keys(seriesData).map(function(id) {
								return parseInt(id, 10);
							});
						} catch (e) {
							// Parse error, will try alternative method below.
						}
					}
				}

				// If we couldn't get series from inline data, try to get from the row's series column.
				if (seriesAfter.length === 0 && postRow) {
					var seriesColumn = postRow.querySelector('td.column-series, td[data-colname="Series"]');
					if (seriesColumn) {
						var seriesLinks = seriesColumn.querySelectorAll('a[href*="series="]');
						seriesLinks.forEach(function(link) {
							var href = link.getAttribute('href');
							var match = href.match(/series=(\d+)/);
							if (match) {
								var seriesId = parseInt(match[1], 10);
								if (seriesAfter.indexOf(seriesId) === -1) {
									seriesAfter.push(seriesId);
								}
							}
						});
					}
				}

				// Find series that were added or removed (not just part number changes).
				var addedSeries = seriesAfter.filter(function(id) {
					return seriesBefore.indexOf(id) === -1;
				});
				var removedSeries = seriesBefore.filter(function(id) {
					return seriesAfter.indexOf(id) === -1;
				});

				// If no series were added or removed, nothing to update.
				if (addedSeries.length === 0 && removedSeries.length === 0) {
					return;
				}

				// Update seriesCountCache for affected series.
				addedSeries.forEach(function(seriesId) {
					// Post was added to series - increment count.
					if (seriesCountCache[seriesId] !== undefined) {
						seriesCountCache[seriesId] = (seriesCountCache[seriesId] || 0) + 1;
					} else {
						// Not in cache yet, initialize to 1 (this post was just added).
						seriesCountCache[seriesId] = 1;
					}
					
					// Update seriesCache if it exists.
					if (seriesCache[seriesId]) {
						seriesCache[seriesId].count = seriesCountCache[seriesId];
					}
				});

				removedSeries.forEach(function(seriesId) {
					// Post was removed from series - decrement count.
					if (seriesCountCache[seriesId] !== undefined) {
						seriesCountCache[seriesId] = Math.max(0, (seriesCountCache[seriesId] || 0) - 1);
					} else {
						// Not in cache, but post was removed, so count should be at least 0.
						seriesCountCache[seriesId] = 0;
					}
					
					// Update seriesCache if it exists.
					if (seriesCache[seriesId]) {
						seriesCache[seriesId].count = seriesCountCache[seriesId];
					}
				});

				// No need to update DOM elements - cache takes priority when reading.
				// When inline data is read later, it will use the cached count value.
			}

			// Update series part fields when series selection changes.
			// Listen for blur events on the series textarea (after user finishes editing/selecting from autocomplete).
			document.addEventListener('blur', function(event) {
				var target = event.target;
				// Check if this is the series taxonomy textarea.
				if (target && target.matches && target.matches('#the-list .inline-edit-row textarea[name="tax_input[series]"]')) {
					var row = target.closest('.inline-edit-row');
					if (row) {
						var postId = 0;
						var postIdMatch = row.id.match(/^edit-(\d+)$/);
						if (postIdMatch) {
							postId = parseInt(postIdMatch[1], 10);
						}
						// Update fields after user finishes editing and moves away from the field.
						updateSeriesPartFields(row, postId);
					}
				}
			}, true); // Use capture phase to ensure we catch the event.
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
