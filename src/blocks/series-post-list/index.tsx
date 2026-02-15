/**
 * Series Post List Block
 */

import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
import Edit from './edit';
import metadata from './block.json';

import './style.scss';
import './editor.scss';

export function migrateV1(
	attributes: Record< string, unknown >
): [ Record< string, unknown >, unknown[][] ] {
	const {
		showSeriesTitle = true,
		showSeriesIcon = true,
		...rest
	} = attributes;

	const innerBlocks: unknown[][] = [];
	if ( showSeriesIcon ) {
		innerBlocks.push( [
			'content-series/series-icon',
			{ isLink: true, size: 60 },
		] );
	}
	if ( showSeriesTitle ) {
		innerBlocks.push( [
			'content-series/series-title',
			{ isLink: true, level: 3 },
		] );
	}

	return [ rest, innerBlocks ];
}

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
	migrate: migrateV1,
};

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => <InnerBlocks.Content />,
	deprecated: [ v1 ],
} );
