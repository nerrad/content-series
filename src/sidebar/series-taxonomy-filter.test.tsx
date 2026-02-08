import SeriesExtensionFields from './series-extension-fields';
import { extendSeriesTermSelector } from './series-taxonomy-filter';

jest.mock( './series-extension-fields', () => ( {
	__esModule: true,
	default: () => null,
} ) );

describe( 'series taxonomy filter', () => {
	const OriginalComponent = ( { slug }: { slug: string } ): JSX.Element => (
		<div data-slug={ slug }>Original</div>
	);

	test( 'returns the original selector for non-series taxonomies', () => {
		const Wrapped = extendSeriesTermSelector( OriginalComponent );
		const output = Wrapped( { slug: 'category' } );

		expect( output.type ).toBe( OriginalComponent );
		expect( output.props.slug ).toBe( 'category' );
	} );

	test( 'appends extension fields when slug is series', () => {
		const Wrapped = extendSeriesTermSelector( OriginalComponent );
		const output = Wrapped( { slug: 'series' } );
		const children = output.props.children as Array< {
			type: unknown;
			props: Record< string, unknown >;
		} >;

		expect( output.type ).toBe( Symbol.for( 'react.fragment' ) );
		expect( children ).toHaveLength( 2 );
		expect( children[ 0 ].type ).toBe( OriginalComponent );
		expect( children[ 0 ].props.slug ).toBe( 'series' );
		expect( children[ 1 ].type ).toBe( SeriesExtensionFields );
	} );
} );
