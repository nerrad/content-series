/**
 * Content Series - Editor Sidebar Panel
 *
 * Registers a plugin that adds a Series panel to the post editor sidebar.
 */

import { registerPlugin } from '@wordpress/plugins';
import SeriesPanel from './series-panel';

import './style.scss';

registerPlugin( 'content-series-sidebar', {
	render: SeriesPanel,
	icon: 'list-view',
} );
