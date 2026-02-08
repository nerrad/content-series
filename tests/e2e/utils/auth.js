async function loginAsAdmin( page ) {
	const username = process.env.WP_ADMIN_USERNAME || 'admin';
	const password = process.env.WP_ADMIN_PASSWORD || 'password';

	await page.goto( '/wp-admin/' );

	if ( ! page.url().includes( '/wp-login.php' ) ) {
		return;
	}

	await page.getByLabel( 'Username or Email Address' ).fill( username );
	await page.getByLabel( 'Password', { exact: true } ).fill( password );
	await page.getByRole( 'button', { name: 'Log In' } ).click();
	await page.waitForURL( /\/wp-admin\/?/ );
}

module.exports = {
	loginAsAdmin,
};
