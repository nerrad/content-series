const { test, expect } = require( './playwright' );
const { loginAsAdmin } = require( './utils/auth' );

test.describe( 'series navigation frontend', () => {
	test( 'renders previous link for a post in a series', async ( {
		page,
	} ) => {
		await loginAsAdmin( page );
		await page.goto( '/wp-admin/' );

		await page.waitForFunction( () =>
			Boolean( window.wp?.apiFetch && window.wp?.data )
		);

		const data = await page.evaluate( async () => {
			const now = Date.now();
			const series = await window.wp.apiFetch( {
				path: '/wp/v2/series',
				method: 'POST',
				data: { name: `E2E Nav Series ${ now }` },
			} );
			const seriesId = series.id;

			const createSeriesPost = async ( title, part, content = '' ) => {
				return window.wp.apiFetch( {
					path: '/wp/v2/posts',
					method: 'POST',
					data: {
						title,
						status: 'publish',
						series: [ seriesId ],
						series_order: { [ seriesId ]: part },
						content,
					},
				} );
			};

			const first = await createSeriesPost(
				`E2E Nav Part 1 ${ now }`,
				1
			);
			const second = await createSeriesPost(
				`E2E Nav Part 2 ${ now }`,
				2
			);
			const navigationHost = await createSeriesPost(
				`E2E Nav Host ${ now }`,
				3,
				'<!-- wp:content-series/navigation {"showTitle":true,"showPartNumbers":true} /-->'
			);

			return {
				firstUrl: first.link,
				secondTitle: second.title.rendered,
				secondUrl: second.link,
				navigationHostUrl: navigationHost.link,
			};
		} );

		await page.goto( data.navigationHostUrl );

		const block = page
			.locator( '.wp-block-content-series-navigation' )
			.filter( { hasText: 'Previous (Part 2)' } )
			.first();
		await expect( block ).toBeVisible();
		await expect( block ).toContainText( 'Previous (Part 2)' );
		await expect( block ).toContainText( data.secondTitle );

		const previousHref = await block
			.locator( '.wp-block-content-series-navigation__prev a' )
			.getAttribute( 'href' );

		expect( previousHref ).toBe( data.secondUrl );
	} );
} );
