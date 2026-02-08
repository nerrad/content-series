const { test, expect } = require( './playwright' );
const { loginAsAdmin } = require( './utils/auth' );

test.describe( 'series navigation frontend', () => {
	test( 'renders previous/next links from inner blocks and the series title block', async ( {
		page,
	} ) => {
		await loginAsAdmin( page );
		await page.goto( '/wp-admin/' );

		await page.waitForFunction( () =>
			Boolean( window.wp?.apiFetch && window.wp?.data )
		);

		const data = await page.evaluate( async () => {
			const now = Date.now();
			const seriesName = `E2E Nav Series ${ now }`;
			const series = await window.wp.apiFetch( {
				path: '/wp/v2/series',
				method: 'POST',
				data: { name: seriesName },
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

			const navigationContent = `
<!-- wp:content-series/navigation -->
<!-- wp:content-series/navigation-link {"direction":"previous","showTitle":true,"showPartNumbers":true} /-->
<!-- wp:content-series/series-title {"isLink":true,"level":3} /-->
<!-- wp:content-series/navigation-link {"direction":"next","showTitle":true,"showPartNumbers":true} /-->
<!-- /wp:content-series/navigation -->`.trim();

			const navigationHost = await createSeriesPost(
				`E2E Nav Host ${ now }`,
				2,
				navigationContent
			);
			const third = await createSeriesPost(
				`E2E Nav Part 3 ${ now }`,
				3
			);

			return {
				firstTitle: first.title.rendered,
				firstUrl: first.link,
				thirdTitle: third.title.rendered,
				thirdUrl: third.link,
				seriesName,
				navigationHostUrl: navigationHost.link,
			};
		} );

		await page.goto( data.navigationHostUrl );

		const block = page
			.locator( '.wp-block-content-series-navigation' )
			.first();
		await expect( block ).toBeVisible();
		await expect( block ).toContainText( 'Previous (Part 1)' );
		await expect( block ).toContainText( data.firstTitle );
		await expect( block ).toContainText( 'Next (Part 3)' );
		await expect( block ).toContainText( data.thirdTitle );
		await expect(
			block.locator( '.wp-block-content-series-navigation__series' )
		).toContainText( data.seriesName );

		const previousHref = await block
			.locator( '.wp-block-content-series-navigation__prev a' )
			.getAttribute( 'href' );
		const nextHref = await block
			.locator( '.wp-block-content-series-navigation__next a' )
			.getAttribute( 'href' );

		expect( previousHref ).toBe( data.firstUrl );
		expect( nextHref ).toBe( data.thirdUrl );
	} );
} );
