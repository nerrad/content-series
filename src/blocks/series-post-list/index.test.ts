import { migrateV1 } from './index';

jest.mock( '@wordpress/blocks', () => ( {
	registerBlockType: jest.fn(),
} ) );
jest.mock( '@wordpress/block-editor', () => ( {
	InnerBlocks: Object.assign( () => null, {
		Content: () => null,
	} ),
} ) );
jest.mock( './edit', () => ( { default: () => null } ) );
jest.mock( './style.scss', () => undefined );
jest.mock( './editor.scss', () => undefined );

describe( 'v1 migration', () => {
	const baseAttributes = {
		showNumbers: true,
		showShortTitle: false,
		highlightCurrent: true,
		context: 'single',
	};

	test( 'preserves both icon and title when both were enabled', () => {
		const [ attrs, innerBlocks ] = migrateV1( {
			...baseAttributes,
			showSeriesTitle: true,
			showSeriesIcon: true,
		} );

		expect( attrs ).toEqual( baseAttributes );
		expect( attrs ).not.toHaveProperty( 'showSeriesTitle' );
		expect( attrs ).not.toHaveProperty( 'showSeriesIcon' );
		expect( innerBlocks ).toEqual( [
			[ 'content-series/series-icon', { isLink: true, size: 60 } ],
			[ 'content-series/series-title', { isLink: true, level: 3 } ],
		] );
	} );

	test( 'omits icon when showSeriesIcon was false', () => {
		const [ attrs, innerBlocks ] = migrateV1( {
			...baseAttributes,
			showSeriesTitle: true,
			showSeriesIcon: false,
		} );

		expect( attrs ).toEqual( baseAttributes );
		expect( innerBlocks ).toEqual( [
			[ 'content-series/series-title', { isLink: true, level: 3 } ],
		] );
	} );

	test( 'omits title when showSeriesTitle was false', () => {
		const [ attrs, innerBlocks ] = migrateV1( {
			...baseAttributes,
			showSeriesTitle: false,
			showSeriesIcon: true,
		} );

		expect( attrs ).toEqual( baseAttributes );
		expect( innerBlocks ).toEqual( [
			[ 'content-series/series-icon', { isLink: true, size: 60 } ],
		] );
	} );

	test( 'returns empty inner blocks when both were disabled', () => {
		const [ attrs, innerBlocks ] = migrateV1( {
			...baseAttributes,
			showSeriesTitle: false,
			showSeriesIcon: false,
		} );

		expect( attrs ).toEqual( baseAttributes );
		expect( innerBlocks ).toEqual( [] );
	} );

	test( 'defaults to both enabled when attributes are absent', () => {
		const [ attrs, innerBlocks ] = migrateV1( { ...baseAttributes } );

		expect( attrs ).toEqual( baseAttributes );
		expect( innerBlocks ).toHaveLength( 2 );
		expect( innerBlocks[ 0 ][ 0 ] ).toBe( 'content-series/series-icon' );
		expect( innerBlocks[ 1 ][ 0 ] ).toBe( 'content-series/series-title' );
	} );
} );
