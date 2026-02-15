const { test, expect } = require( './playwright' );
const { loginAsAdmin } = require( './utils/auth' );

test.describe( 'series post list frontend', () => {
	test( 'renders header from inner blocks and post list with new format', async ( {
		page,
	} ) => {
		await loginAsAdmin( page );
		await page.goto( '/wp-admin/' );

		await page.waitForFunction( () =>
			Boolean( window.wp?.apiFetch && window.wp?.data )
		);

		const data = await page.evaluate( async () => {
			const now = Date.now();
			const seriesName = `E2E Post List Series ${ now }`;
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

			const first = await createSeriesPost( `E2E PL Part 1 ${ now }`, 1 );

			const postListContent = `
<!-- wp:content-series/post-list -->
<!-- wp:content-series/series-icon {"isLink":true,"size":60} /-->
<!-- wp:content-series/series-title {"isLink":true,"level":3} /-->
<!-- /wp:content-series/post-list -->`.trim();

			const hostPost = await createSeriesPost(
				`E2E PL Host ${ now }`,
				2,
				postListContent
			);

			const third = await createSeriesPost( `E2E PL Part 3 ${ now }`, 3 );

			return {
				firstTitle: first.title.rendered,
				thirdTitle: third.title.rendered,
				hostTitle: hostPost.title.rendered,
				hostUrl: hostPost.link,
				seriesName,
			};
		} );

		await page.goto( data.hostUrl );

		const block = page
			.locator( '.wp-block-content-series-post-list' )
			.first();
		await expect( block ).toBeVisible();

		// Header should contain the series title from inner blocks.
		const header = block.locator(
			'.wp-block-content-series-post-list__header'
		);
		await expect( header ).toContainText( data.seriesName );

		// Post list should contain all three posts.
		const items = block.locator(
			'.wp-block-content-series-post-list__items'
		);
		await expect( items ).toContainText( data.firstTitle );
		await expect( items ).toContainText( data.hostTitle );
		await expect( items ).toContainText( data.thirdTitle );

		// Current post should be highlighted.
		const currentItem = items.locator( 'li.is-current' );
		await expect( currentItem ).toContainText( data.hostTitle );
		await expect( currentItem.locator( 'span' ) ).toBeVisible();
	} );

	test( 'renders header via legacy fallback for self-closing block format', async ( {
		page,
	} ) => {
		await loginAsAdmin( page );
		await page.goto( '/wp-admin/' );

		await page.waitForFunction( () =>
			Boolean( window.wp?.apiFetch && window.wp?.data )
		);

		const data = await page.evaluate( async () => {
			const now = Date.now();
			const seriesName = `E2E PL Legacy Series ${ now }`;
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
				`E2E PL Legacy Part 1 ${ now }`,
				1
			);

			// Self-closing format (legacy) — no inner blocks.
			const legacyContent = '<!-- wp:content-series/post-list /-->';

			const hostPost = await createSeriesPost(
				`E2E PL Legacy Host ${ now }`,
				2,
				legacyContent
			);

			return {
				firstTitle: first.title.rendered,
				hostTitle: hostPost.title.rendered,
				hostUrl: hostPost.link,
				seriesName,
			};
		} );

		await page.goto( data.hostUrl );

		const block = page
			.locator( '.wp-block-content-series-post-list' )
			.first();
		await expect( block ).toBeVisible();

		// Header should still render via the legacy fallback path.
		const header = block.locator(
			'.wp-block-content-series-post-list__header'
		);
		await expect( header ).toContainText( data.seriesName );

		// Legacy path adds explicit CSS classes to child blocks.
		const legacyIcon = header.locator(
			'.wp-block-content-series-post-list__icon'
		);
		await expect( legacyIcon ).toBeVisible();

		const legacyTitle = header.locator(
			'.wp-block-content-series-post-list__title'
		);
		await expect( legacyTitle ).toBeVisible();

		// Post list should render.
		const items = block.locator(
			'.wp-block-content-series-post-list__items'
		);
		await expect( items ).toContainText( data.firstTitle );
		await expect( items ).toContainText( data.hostTitle );
	} );
} );
