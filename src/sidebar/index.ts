/**
 * Content Series - Editor Sidebar Extension
 *
 * Extends the default Series taxonomy panel with additional fields
 * (order in series, short title) using the editor.PostTaxonomyType filter.
 */

import { addFilter } from '@wordpress/hooks';

import { extendSeriesTermSelector } from './series-taxonomy-filter';

import './style.scss';

addFilter(
	'editor.PostTaxonomyType',
	'content-series/extend-series-selector',
	extendSeriesTermSelector
);
