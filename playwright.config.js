const path = require( 'path' );
const { requireFromWpScripts } = require( './tests/e2e/resolve-from-wp-scripts' );

const { defineConfig, devices } = requireFromWpScripts( '@playwright/test' );

module.exports = defineConfig( {
	testDir: './tests/e2e',
	testMatch: '*.spec.js',
	fullyParallel: true,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: process.env.CI ? 1 : undefined,
	timeout: 60_000,
	expect: {
		timeout: 10_000,
	},
	reporter: [
		[ 'list' ],
		[ 'html', { outputFolder: 'playwright-report', open: 'never' } ],
	],
	use: {
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
		trace: 'retain-on-failure',
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},
		{
			name: 'firefox',
			use: { ...devices['Desktop Firefox'] },
		},
		{
			name: 'webkit',
			use: { ...devices['Desktop Safari'] },
		},
	],
	outputDir: path.resolve( __dirname, 'test-results' ),
} );
