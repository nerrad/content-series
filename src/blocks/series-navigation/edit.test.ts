import {
	ARROW_STYLES,
	getArrowStyle,
	getNavigationPosts,
	getNavigationViewState,
} from './edit';

import type { SeriesData } from '../../types';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@wordpress/block-editor', () => ( {
	InspectorControls: () => null,
	useBlockProps: () => ( {} ),
} ) );
jest.mock( '@wordpress/components', () => ( {
	PanelBody: () => null,
	Placeholder: () => null,
	SelectControl: () => null,
	Spinner: () => null,
	TextControl: () => null,
	ToggleControl: () => null,
} ) );
jest.mock( '@wordpress/core-data', () => ( { store: {} } ) );
jest.mock( '@wordpress/data', () => ( { useSelect: () => ( {} ) } ) );
jest.mock( '@wordpress/element', () => ( {
	useEffect: () => undefined,
	useState: ( value: unknown ) => [ value, jest.fn() ],
} ) );
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

describe( 'series navigation edit helpers', () => {
	const data: SeriesData = {
		series: {
			id: 1,
			name: 'Test Series',
			slug: 'test-series',
			description: '',
			count: 3,
		},
		posts: [
			{
				id: 10,
				title: 'Part 1',
				url: '/part-1',
				series_part: 1,
				status: 'publish',
				date: '2026-01-01T00:00:00',
			},
			{
				id: 20,
				title: 'Part 2',
				url: '/part-2',
				series_part: 2,
				status: 'publish',
				date: '2026-01-02T00:00:00',
			},
			{
				id: 30,
				title: 'Part 3',
				url: '/part-3',
				series_part: 3,
				status: 'publish',
				date: '2026-01-03T00:00:00',
			},
		],
	};

	test( 'finds previous and next posts for the current post', () => {
		const nav = getNavigationPosts( data, 20 );

		expect( nav.prev?.id ).toBe( 10 );
		expect( nav.next?.id ).toBe( 30 );
	} );

	test( 'returns no navigation when the post is missing from the series', () => {
		const nav = getNavigationPosts( data, 999 );

		expect( nav.prev ).toBeNull();
		expect( nav.next ).toBeNull();
	} );

	test( 'resolves view state transitions correctly', () => {
		expect(
			getNavigationViewState( {
				isLoading: true,
				series: [ 1 ],
				error: null,
				prev: null,
				next: null,
			} )
		).toBe( 'loading' );

		expect(
			getNavigationViewState( {
				isLoading: false,
				series: [],
				error: null,
				prev: null,
				next: null,
			} )
		).toBe( 'no-series' );

		expect(
			getNavigationViewState( {
				isLoading: false,
				series: [ 1 ],
				error: 'failed',
				prev: null,
				next: null,
			} )
		).toBe( 'error' );

		expect(
			getNavigationViewState( {
				isLoading: false,
				series: [ 1 ],
				error: null,
				prev: null,
				next: null,
			} )
		).toBe( 'no-navigation' );

		expect(
			getNavigationViewState( {
				isLoading: false,
				series: [ 1 ],
				error: null,
				prev: data.posts[ 0 ],
				next: null,
			} )
		).toBe( 'ready' );
	} );

	test( 'returns configured arrow styles and falls back to defaults', () => {
		expect( getArrowStyle( 'chevron' ) ).toEqual( ARROW_STYLES.chevron );
		expect( getArrowStyle( 'none' ) ).toEqual( ARROW_STYLES.none );
		expect( getArrowStyle( 'invalid' as never ) ).toEqual(
			ARROW_STYLES.arrow
		);
	} );
} );
