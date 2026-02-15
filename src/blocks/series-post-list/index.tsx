/**
 * Series Post List Block
 */

import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
import Edit from './edit';
import metadata from './block.json';

import './style.scss';
import './editor.scss';

const v1 = {
	attributes: {
		...metadata.attributes,
		showSeriesTitle: {
			type: 'boolean' as const,
			default: true,
		},
		showSeriesIcon: {
			type: 'boolean' as const,
			default: true,
		},
	},
	save: () => null,
	migrate(
		attributes: Record< string, unknown >
	): [ Record< string, unknown >, unknown[] ] {
		const {
			showSeriesTitle: _title,
			showSeriesIcon: _icon,
			...rest
		} = attributes;
		return [ rest, [] ];
	},
};

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => <InnerBlocks.Content />,
	deprecated: [ v1 ],
} );
