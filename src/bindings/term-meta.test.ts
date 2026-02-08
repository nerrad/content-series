describe( 'term meta block bindings', () => {
	const registerBlockBindingsSource = jest.fn();
	const getEntityRecord = jest.fn();

	const loadModule = (): void => {
		jest.resetModules();

		jest.doMock( '@wordpress/blocks', () => ( {
			registerBlockBindingsSource,
		} ) );

		jest.doMock( '@wordpress/i18n', () => ( {
			__: ( text: string ) => text,
		} ) );

		jest.doMock( '@wordpress/core-data', () => ( {
			store: 'core',
		} ) );

		jest.isolateModules( () => {
			require( './term-meta' );
		} );
	};

	beforeEach( () => {
		registerBlockBindingsSource.mockReset();
		getEntityRecord.mockReset();
	} );

	test( 'registers content-series term meta source', () => {
		loadModule();

		expect( registerBlockBindingsSource ).toHaveBeenCalledTimes( 1 );

		const sourceConfig = registerBlockBindingsSource.mock.calls[ 0 ][ 0 ] as {
			name: string;
			usesContext: string[];
			getFieldsList: ( args: {
				context: Record< string, unknown >;
			} ) => Array< {
				args: { key: string };
			} >;
		};

		expect( sourceConfig.name ).toBe( 'content-series/term-meta' );
		expect( sourceConfig.usesContext ).toEqual( [ 'termId', 'taxonomy' ] );

		expect(
			sourceConfig.getFieldsList( {
				context: {},
			} )
		).toEqual( [] );

		expect(
			sourceConfig.getFieldsList( {
				context: { termId: 10, taxonomy: 'series' },
			} )[ 0 ]?.args.key
		).toBe( 'series_icon' );
	} );

	test( 'maps binding args to term meta values', () => {
		loadModule();

		const sourceConfig = registerBlockBindingsSource.mock.calls[ 0 ][ 0 ] as {
			getValues: ( args: {
				bindings: Record< string, { args?: { key?: string } } >;
				context: { termId?: number; taxonomy?: string };
				select: ( store: unknown ) => {
					getEntityRecord: (
						kind: string,
						taxonomy: string,
						termId: number
					) => { meta?: Record< string, unknown > } | undefined;
				};
			} ) => Record< string, string >;
		};

		getEntityRecord.mockReturnValue( {
			meta: {
				series_icon: 'https://example.com/icon.png',
				series_icon_id: 99,
			},
		} );

		const values = sourceConfig.getValues( {
			bindings: {
				url: { args: { key: 'series_icon' } },
				id: { args: { key: 'series_icon_id' } },
				empty: {},
			},
			context: {
				termId: 21,
				taxonomy: 'series',
			},
			select: () => ( {
				getEntityRecord,
			} ),
		} );

		expect( values.url ).toBe( 'https://example.com/icon.png' );
		expect( values.id ).toBe( '99' );
		expect( values.empty ).toBe( '' );
		expect( getEntityRecord ).toHaveBeenCalledWith(
			'taxonomy',
			'series',
			21
		);
	} );
} );
