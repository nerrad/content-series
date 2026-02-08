const { test, expect } = require( './playwright' );
const { loginAsAdmin } = require( './utils/auth' );

test.describe( 'series extension sidebar fields', () => {
	test( 'shows plugin fields when a series is assigned', async ( {
		page,
	} ) => {
		await loginAsAdmin( page );
		await page.goto( '/wp-admin/post-new.php' );

		await page.waitForFunction(
			() => Boolean( window.wp?.apiFetch && window.wp?.data )
		);

		const series = await page.evaluate( async () => {
			const label = `E2E Sidebar Series ${ Date.now() }`;
			const existing = await window.wp.apiFetch( {
				path: `/wp/v2/series?search=${ encodeURIComponent(
					label
				) }&per_page=1`,
			} );

			if ( Array.isArray( existing ) && existing[ 0 ] ) {
				return existing[ 0 ];
			}

			return window.wp.apiFetch( {
				path: '/wp/v2/series',
				method: 'POST',
				data: { name: label },
			} );
		} );

		await page.evaluate( ( seriesId ) => {
			window.wp.data.dispatch( 'core/editor' ).editPost( {
				series: [ seriesId ],
				series_order: { [ seriesId ]: 3 },
			} );
		}, series.id );

		await expect( page.getByText( 'Order in Series' ) ).toBeVisible();
		await expect( page.getByLabel( 'Short Title (optional)' ) ).toBeVisible();

		const orderInput = page.locator(
			'.content-series-extension__order input[type="number"]'
		);
		await orderInput.fill( '7' );
		await page.getByLabel( 'Short Title (optional)' ).fill( 'Sidebar Title' );

		const editedValues = await page.evaluate( ( seriesId ) => {
			const editor = window.wp.data.select( 'core/editor' );
			const meta = editor.getEditedPostAttribute( 'meta' ) || {};
			const seriesOrder =
				editor.getEditedPostAttribute( 'series_order' ) || {};

			return {
				shortTitle: meta._spost_short_title,
				seriesOrder: seriesOrder[ seriesId ],
			};
		}, series.id );

		expect( editedValues.shortTitle ).toBe( 'Sidebar Title' );
		expect( editedValues.seriesOrder ).toBe( 7 );
	} );
} );
