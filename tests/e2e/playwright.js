const { requireFromWpScripts } = require( './resolve-from-wp-scripts' );

const playwright = requireFromWpScripts( '@playwright/test' );

module.exports = playwright;
