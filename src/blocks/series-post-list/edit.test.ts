import { getListTag, getPostDisplayTitle, getPostListViewState } from './edit';

import type { SeriesData, SeriesPost } from '../../types';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@wordpress/block-editor', () => ( {
	InspectorControls: () => null,
	useBlockProps: () => ( {} ),
} ) );
jest.mock( '@wordpress/components', () => ( {
	PanelBody: () => null,
	Placeholder: () => null,
	Spinner: () => null,
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

describe( 'series post list edit helpers', () => {
	const post: SeriesPost = {
		id: 10,
		title: 'Long Post Title',
		short_title: 'Short Title',
		url: '/post',
		series_part: 1,
		status: 'publish',
		date: '2026-01-01T00:00:00',
	};

	const seriesData: SeriesData = {
		series: {
			id: 5,
			name: 'Test Series',
			slug: 'test-series',
			description: '',
			count: 1,
		},
		posts: [ post ],
	};

	test( 'returns ordered or unordered list tags based on settings', () => {
		expect( getListTag( true ) ).toBe( 'ol' );
		expect( getListTag( false ) ).toBe( 'ul' );
	} );

	test( 'prefers short titles only when enabled and available', () => {
		expect( getPostDisplayTitle( post, true ) ).toBe( 'Short Title' );
		expect( getPostDisplayTitle( post, false ) ).toBe( 'Long Post Title' );

		expect(
			getPostDisplayTitle(
				{
					...post,
					short_title: '',
				},
				true
			)
		).toBe( 'Long Post Title' );
	} );

	test( 'resolves post list view state transitions correctly', () => {
		expect(
			getPostListViewState( {
				isLoading: true,
				series: [ 5 ],
				error: null,
				seriesData: null,
			} )
		).toBe( 'loading' );

		expect(
			getPostListViewState( {
				isLoading: false,
				series: [],
				error: null,
				seriesData: null,
			} )
		).toBe( 'no-series' );

		expect(
			getPostListViewState( {
				isLoading: false,
				series: [ 5 ],
				error: 'failed',
				seriesData: null,
			} )
		).toBe( 'error' );

		expect(
			getPostListViewState( {
				isLoading: false,
				series: [ 5 ],
				error: null,
				seriesData: null,
			} )
		).toBe( 'no-data' );

		expect(
			getPostListViewState( {
				isLoading: false,
				series: [ 5 ],
				error: null,
				seriesData,
			} )
		).toBe( 'ready' );
	} );
} );
