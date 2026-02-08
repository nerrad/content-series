describe( 'series catalog variation', () => {
	const registerBlockVariation = jest.fn();

	const loadModule = (): {
		getInnerBlocks: () => Array< unknown[] >;
	} => {
		jest.resetModules();

		( globalThis as Record< string, unknown > ).contentSeriesVariations = {
			title: 'Series Catalog',
			description: 'Series catalog variation',
		};

		jest.doMock( '@wordpress/blocks', () => ( {
			registerBlockVariation,
		} ) );

		let exports: { getInnerBlocks: () => Array< unknown[] > } | undefined;
		jest.isolateModules( () => {
			exports = require( './index' );
		} );

		return exports as { getInnerBlocks: () => Array< unknown[] > };
	};

	beforeEach( () => {
		registerBlockVariation.mockReset();
	} );

	test( 'registers the expected variation attributes', () => {
		loadModule();

		expect( registerBlockVariation ).toHaveBeenCalledTimes( 1 );

		const [ blockName, variation ] = registerBlockVariation.mock
			.calls[ 0 ] as [
			string,
			{
				name: string;
				attributes: {
					termQuery: { taxonomy: string; perPage: number };
				};
				scope: string[];
				isActive: ( attrs: {
					termQuery?: { taxonomy?: string };
				} ) => boolean;
			},
		];

		expect( blockName ).toBe( 'core/terms-query' );
		expect( variation.name ).toBe( 'content-series/catalog' );
		expect( variation.attributes.termQuery.taxonomy ).toBe( 'series' );
		expect( variation.attributes.termQuery.perPage ).toBe( 100 );
		expect( variation.scope ).toEqual( [ 'inserter', 'block' ] );
		expect(
			variation.isActive( { termQuery: { taxonomy: 'series' } } )
		).toBe( true );
		expect(
			variation.isActive( { termQuery: { taxonomy: 'category' } } )
		).toBe( false );
	} );

	test( 'builds inner blocks with image binding for series icon', () => {
		const { getInnerBlocks } = loadModule();
		const innerBlocks = getInnerBlocks() as any[][];
		const imageBlock = innerBlocks[ 0 ][ 2 ][ 0 ][ 2 ][ 0 ] as [
			string,
			{
				metadata: {
					bindings: {
						url: {
							source: string;
							args: { key: string };
						};
					};
				};
			},
		];

		expect( imageBlock[ 0 ] ).toBe( 'core/image' );
		expect( imageBlock[ 1 ].metadata.bindings.url.source ).toBe(
			'content-series/term-meta'
		);
		expect( imageBlock[ 1 ].metadata.bindings.url.args.key ).toBe(
			'series_icon'
		);
	} );
} );
