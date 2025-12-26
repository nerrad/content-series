/**
 * Series Navigation Block
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';

import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit: Edit,
	// Server-side rendering only
	save: () => null,
} );
