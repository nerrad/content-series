import {
	buildCurrentSeriesQuery,
	parseSeriesOrderInput,
	shouldSuggestNextPart,
} from './series-extension-fields';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@wordpress/components', () => ( {
	Spinner: () => null,
	TextControl: () => null,
} ) );
jest.mock( '@wordpress/core-data', () => ( { store: {} } ) );
jest.mock( '@wordpress/data', () => ( {
	useDispatch: () => ( { editPost: jest.fn() } ),
	useSelect: () => ( {} ),
} ) );
jest.mock( '@wordpress/editor', () => ( { store: {} } ) );
jest.mock( '@wordpress/element', () => ( {
	useCallback: ( fn: unknown ) => fn,
	useEffect: () => undefined,
	useMemo: ( fn: () => unknown ) => fn(),
	useRef: () => ( { current: false } ),
} ) );
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

describe( 'series extension fields helpers', () => {
	test( 'builds taxonomy term query only when series ids exist', () => {
		expect( buildCurrentSeriesQuery( [] ) ).toBeNull();
		expect( buildCurrentSeriesQuery( [ 12, 34 ] ) ).toEqual( {
			include: [ 12, 34 ],
			per_page: 100,
		} );
	} );

	test( 'suggests next part only for new or draft posts', () => {
		expect( shouldSuggestNextPart( true, 'publish' ) ).toBe( true );
		expect( shouldSuggestNextPart( false, 'draft' ) ).toBe( true );
		expect( shouldSuggestNextPart( false, 'auto-draft' ) ).toBe( true );
		expect( shouldSuggestNextPart( false, 'publish' ) ).toBe( false );
	} );

	test( 'parses series order values with a safe default', () => {
		expect( parseSeriesOrderInput( '7' ) ).toBe( 7 );
		expect( parseSeriesOrderInput( '0' ) ).toBe( 1 );
		expect( parseSeriesOrderInput( 'not-a-number' ) ).toBe( 1 );
	} );
} );
