const { test, expect } = require( './playwright' );
const { loginAsAdmin } = require( './utils/auth' );

test.describe( 'series extension sidebar fields', () => {
	test( 'shows plugin fields when a series is assigned', async ( {
		page,
	} ) => {
		await loginAsAdmin( page );

		// Navigate to wp-admin so we can use apiFetch to create test data.
		await page.goto( '/wp-admin/' );
		await page.waitForFunction( () =>
			Boolean( window.wp?.apiFetch )
		);

		// Create a series and a draft post with the series assigned via
		// REST API.  Opening the editor for an existing post ensures
		// _links (including wp:action-assign-series) is present in the
		// initial page load, which the sidebar component requires.
		const postEditUrl = await page.evaluate( async () => {
			const label = `E2E Sidebar Series ${ Date.now() }`;
			const series = await window.wp.apiFetch( {
				path: '/wp/v2/series',
				method: 'POST',
				data: { name: label },
			} );

			const post = await window.wp.apiFetch( {
				path: '/wp/v2/posts',
				method: 'POST',
				data: {
					title: `E2E Sidebar Post ${ Date.now() }`,
					status: 'draft',
					series: [ series.id ],
					series_order: { [ series.id ]: 3 },
				},
			} );

			return `/wp-admin/post.php?post=${ post.id }&action=edit`;
		} );

		await page.goto( postEditUrl );

		await page.waitForFunction( () =>
			Boolean( window.wp?.data?.select( 'core/editor' )?.getCurrentPost() )
		);

		// Dismiss the Welcome Guide — this is WordPress core onboarding UI,
		// not part of our plugin's user flow.  Standard practice in WP E2E
		// tests is to disable it via preferences so it doesn't block the
		// test from interacting with the editor.
		await page.evaluate( () => {
			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-post', 'welcomeGuide', false );
		} );

		// Open the Post settings sidebar via the toolbar button (if closed).
		const settingsToggle = page.getByRole( 'button', {
			name: 'Settings',
			exact: true,
		} );
		if ( ! ( await settingsToggle.getAttribute( 'aria-pressed' ) === 'true' ) ) {
			await settingsToggle.click();
		}

		// Expand the Series taxonomy panel in the sidebar.
		const seriesPanel = page.getByRole( 'button', { name: 'Series' } );
		await seriesPanel.scrollIntoViewIfNeeded();
		await seriesPanel.click();

		await expect( page.getByText( 'Order in Series' ) ).toBeVisible();
		await expect(
			page.getByLabel( 'Short Title (optional)' )
		).toBeVisible();

		const orderInput = page.locator(
			'.content-series-extension__order input[type="number"]'
		);
		await orderInput.fill( '7' );
		await page
			.getByLabel( 'Short Title (optional)' )
			.fill( 'Sidebar Title' );

		const editedValues = await page.evaluate( () => {
			const editor = window.wp.data.select( 'core/editor' );
			const meta = editor.getEditedPostAttribute( 'meta' ) || {};
			const seriesOrder =
				editor.getEditedPostAttribute( 'series_order' ) || {};
			const seriesIds =
				editor.getEditedPostAttribute( 'series' ) || [];
			const seriesId = seriesIds[ 0 ];

			return {
				shortTitle: meta._spost_short_title,
				seriesOrder: seriesOrder[ seriesId ],
			};
		} );

		expect( editedValues.shortTitle ).toBe( 'Sidebar Title' );
		expect( editedValues.seriesOrder ).toBe( 7 );
	} );
} );
