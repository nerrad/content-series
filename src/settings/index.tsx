/**
 * Content Series - Admin Settings Page
 */

import { createRoot } from '@wordpress/element';
import SettingsPage from './settings-page';

import './style.scss';

// Wait for DOM ready
document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'content-series-settings' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <SettingsPage /> );
	}
} );
