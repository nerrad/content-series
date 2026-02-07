/**
 * Block Variations Entry Point
 *
 * Registers the series catalog block variation for core/terms-query.
 */

import { registerBlockVariation } from '@wordpress/blocks';

declare const contentSeriesVariations: {
	title: string;
	description: string;
};

interface TermsQueryVariationAttributes {
	termQuery?: {
		taxonomy?: string;
	};
}

/**
 * Get the inner blocks structure for the variation.
 *
 * @return {Array} Inner blocks array structure.
 */
function getInnerBlocks() {
	return [
		[
			'core/term-template',
			{
				layout: {
					type: 'grid',
					columnCount: 3,
				},
			},
			[
				[
					'core/group',
					{
						style: {
							spacing: {
								blockGap: '0.5rem',
							},
						},
						layout: {
							type: 'flex',
							orientation: 'vertical',
							justifyContent: 'center',
						},
					},
					[
						[
							'core/image',
							{
								width: '150px',
								height: '150px',
								scale: 'contain',
								metadata: {
									bindings: {
										url: {
											source: 'content-series/term-meta',
											args: {
												key: 'series_icon',
											},
										},
									},
								},
							},
							[],
						],
						[
							'core/term-name',
							{
								isLink: true,
								textAlign: 'center',
								level: 3,
							},
							[],
						],
						[
							'core/term-count',
							{
								textAlign: 'center',
							},
							[],
						],
						[
							'core/term-description',
							{
								textAlign: 'center',
							},
							[],
						],
					],
				],
			],
		],
	];
}

registerBlockVariation( 'core/terms-query', {
	name: 'content-series/catalog',
	title: contentSeriesVariations.title,
	description: contentSeriesVariations.description,
	category: 'theme',
	keywords: [ 'series', 'catalog', 'list' ],
	attributes: {
		termQuery: {
			perPage: 100,
			taxonomy: 'series',
			order: 'asc',
			orderBy: 'name',
			include: [],
			hideEmpty: false,
			showNested: false,
			inherit: false,
		},
	},
	isActive( blockAttributes: TermsQueryVariationAttributes ) {
		return blockAttributes.termQuery?.taxonomy === 'series';
	},
	innerBlocks: getInnerBlocks(),
	scope: [ 'inserter', 'block' ],
} );
