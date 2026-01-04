/**
 * Quick Edit Series Parts
 *
 * Adds series part number editing to WordPress Quick Edit interface.
 * Uses IIFE to avoid polluting global scope.
 *
 * Global dependency: contentSeriesQuickEditData (provided via wp_add_inline_script)
 */
(function() {
	// Timeout constants (in milliseconds).
	var DOM_UPDATE_DELAY = 200;        // Wait for WordPress to update DOM after save
	var AJAX_CLEANUP_TIMEOUT = 10000;  // Force remove AJAX handler after 10 seconds
	var FALLBACK_SAVE_DELAY = 1000;    // Assume save completed if jQuery unavailable

	// Get REST URL from inline script data (global provided by WordPress).
	var restUrl = contentSeriesQuickEditData.restUrl;

	/**
	 * Cache Strategy:
	 * - seriesCache: Stores series objects by ID {id, name, count}
	 * - seriesCountCache: Canonical source for total parts count (seriesId -> count)
	 * - Priority: seriesCountCache > API count > inline data count
	 * - Updates: Incremented/decremented when posts added/removed from series
	 */
	var seriesCache = {};
	var seriesCountCache = {};

	/**
	 * Normalize series name for case-insensitive comparison.
	 *
	 * @param {string} name - Series name to normalize
	 * @return {string} Normalized name (lowercase, trimmed, single spaces)
	 */
	function normalizeSeriesName(name) {
		if (!name) return '';
		return name.toLowerCase().trim().replace(/\s+/g, ' ');
	}

	/**
	 * Get series data by name.
	 *
	 * Lookup order:
	 * 1. Check seriesCache
	 * 2. Check inline data (DOM elements with data-series-data attribute)
	 * 3. Fetch from REST API if not found
	 *
	 * @param {string} seriesName - Name of series to lookup
	 * @param {Function} callback - Called with series object {id, name, count} or null
	 */
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
				// JSON parse error - inline data may be malformed.
				console.warn('Failed to parse inline series data:', e);
			}
		}

		// Not found in inline data, fetch from API.
		// Search for series by name (WordPress REST API search does partial matching).
		// Note: Limited to 100 results (WordPress REST API default maximum).
		// Sites with 100+ series may not find all matches. To increase, add a filter:
		// add_filter( 'rest_series_query', function($args) { $args['number'] = 500; return $args; } );
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

	/**
	 * Get current part numbers for a post from inline data.
	 *
	 * Reads series part data from the inline container element
	 * (added by WordPress via add_inline_data hook).
	 *
	 * @param {number} postId - Post ID
	 * @param {Function} callback - Called with object mapping seriesId -> partNumber
	 */
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

	/**
	 * Create a series part input field element.
	 *
	 * Creates DOM structure:
	 * <div class="content-series-part-field show">
	 *   <label>
	 *     <span class="series-name">{name}</span> - Part
	 *     <input type="number" name="series_part_{id}" value="{part}" />
	 *     of {count} ( -> {preview} )
	 *   </label>
	 * </div>
	 *
	 * @param {Object} series - Series object {id, name, count}
	 * @param {number} currentPart - Current part number for this post
	 * @param {boolean} isNewAddition - Whether post is newly added to this series
	 * @return {HTMLElement} The field element
	 */
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

		// Create elements safely using DOM methods to prevent XSS.
		var seriesNameSpan = document.createElement('span');
		seriesNameSpan.className = 'series-name';
		seriesNameSpan.textContent = series.name; // textContent escapes HTML
		label.appendChild(seriesNameSpan);

		var partLabelSpan = document.createElement('span');
		partLabelSpan.className = 'part-label';
		partLabelSpan.textContent = ' - Part';
		label.appendChild(partLabelSpan);

		var input = document.createElement('input');
		input.type = 'number';
		input.name = 'series_part_' + series.id;
		input.value = currentPart;
		input.min = '1';
		input.className = 'content-series-part-input';
		input.setAttribute('data-series-id', series.id);
		label.appendChild(input);

		var totalPartsSpan = document.createElement('span');
		totalPartsSpan.className = 'total-parts';
		totalPartsSpan.textContent = totalPartsText;
		label.appendChild(totalPartsSpan);

		field.appendChild(label);
		return field;
	}

	/**
	 * Update series part fields dynamically for a Quick Edit row.
	 *
	 * Event flow:
	 * 1. Parse series names from textarea (comma or newline separated)
	 * 2. For each series, fetch data via getSeriesData()
	 * 3. Get current part numbers via getCurrentParts()
	 * 4. Create field for each series via createSeriesPartField()
	 * 5. Append fields to container and show/hide as needed
	 *
	 * Request counter prevents stale callbacks from overwriting newer requests.
	 *
	 * @param {HTMLElement} row - Quick Edit table row element
	 * @param {number} postId - Post ID being edited
	 */
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


	/**
	 * WordPress Quick Edit Integration
	 *
	 * Extends WordPress's inlineEditPost object to add series parts functionality:
	 * - inlineEditPost.edit: Called when Quick Edit opens - populates part fields
	 * - inlineEditPost.save: Called when Save button clicked - tracks changes for cache updates
	 */

	// Extend inlineEditPost.edit to populate series part fields when Quick Edit opens.
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
					// JSON parse error - inline data may be malformed.
					console.warn('Failed to parse series data before save for post ' + post_id + ':', e);
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

				// Clear the cleanup timeout since handler fired successfully.
				if (cleanupTimeoutId) {
					clearTimeout(cleanupTimeoutId);
				}

				// Wait for DOM to update (WordPress updates the row asynchronously).
				setTimeout(function() {
					if (post_id > 0) {
						updateSeriesCountsAfterSave(post_id, seriesBeforeSave[post_id] || []);
						// Clean up.
						delete seriesBeforeSave[post_id];
					}
				}, DOM_UPDATE_DELAY);
			}
		};

		// Attach handler (using jQuery since WordPress uses it for AJAX).
		// If jQuery is not available, we'll assume success after a delay.
		if (typeof jQuery !== 'undefined') {
			jQuery(document).on('ajaxComplete', ajaxCompleteHandler);

			// Add timeout to forcibly remove handler if AJAX never completes (prevents memory leak).
			var cleanupTimeoutId = setTimeout(function() {
				jQuery(document).off('ajaxComplete', ajaxCompleteHandler);
				// Clean up tracking data.
				if (post_id > 0 && seriesBeforeSave[post_id]) {
					delete seriesBeforeSave[post_id];
				}
			}, AJAX_CLEANUP_TIMEOUT);
		} else {
			// Fallback: assume save completed successfully after a delay.
			setTimeout(function() {
				if (post_id > 0) {
					updateSeriesCountsAfterSave(post_id, seriesBeforeSave[post_id] || []);
					delete seriesBeforeSave[post_id];
				}
			}, FALLBACK_SAVE_DELAY);
		}
	};

	/**
	 * Update series counts in cache after a post is saved.
	 *
	 * Compares series assignments before/after save to detect additions/removals.
	 * Updates seriesCountCache by incrementing for additions, decrementing for removals.
	 * Only modifies cache - doesn't update DOM (cache takes priority on next read).
	 *
	 * Note: Only updates when posts are added/removed from series, not for part number changes.
	 *
	 * @param {number} postId - Post ID that was saved
	 * @param {Array<number>} seriesBefore - Array of series IDs before save
	 */
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
					console.warn('Failed to parse series data after save, will try alternative method:', e);
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
			// Post was removed from series - decrement count if cached.
			// If not cached, we can't know the correct count, so don't add stale data.
			// Next access will fetch fresh data from API or inline data.
			if (seriesCountCache[seriesId] !== undefined) {
				seriesCountCache[seriesId] = Math.max(0, (seriesCountCache[seriesId] || 0) - 1);

				// Update seriesCache if it exists.
				if (seriesCache[seriesId]) {
					seriesCache[seriesId].count = seriesCountCache[seriesId];
				}
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
