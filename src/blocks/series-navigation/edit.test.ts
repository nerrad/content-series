import {
	NAVIGATION_ALLOWED_BLOCKS,
	NAVIGATION_TEMPLATE,
	getNavigationTemplate,
} from './edit';

jest.mock( '@wordpress/block-editor', () => ( {
	InnerBlocks: Object.assign( () => null, {
		Content: () => null,
	} ),
	useBlockProps: () => ( {} ),
} ) );

describe( 'series navigation wrapper edit helpers', () => {
	test( 'exposes expected allowed inner blocks', () => {
		expect( NAVIGATION_ALLOWED_BLOCKS ).toEqual( [
			'content-series/navigation-link',
			'content-series/series-title',
			'content-series/series-icon',
		] );
	} );

	test( 'provides a default template with previous, title, and next blocks', () => {
		expect( NAVIGATION_TEMPLATE ).toEqual( [
			[ 'content-series/navigation-link', { direction: 'previous' } ],
			[ 'content-series/series-title', {} ],
			[ 'content-series/navigation-link', { direction: 'next' } ],
		] );
	} );

	test( 'returns a cloned template structure', () => {
		const first = getNavigationTemplate();
		const second = getNavigationTemplate();

		expect( first ).toEqual( second );
		expect( first ).not.toBe( second );
		expect( first[ 0 ][ 1 ] ).not.toBe( second[ 0 ][ 1 ] );
	} );
} );
