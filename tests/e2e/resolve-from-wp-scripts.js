const path = require( 'path' );

const wpScriptsDirectory = path.dirname(
	require.resolve( '@wordpress/scripts/package.json' )
);

function resolveFromWpScripts( moduleName ) {
	return require.resolve( moduleName, {
		paths: [ wpScriptsDirectory ],
	} );
}

function requireFromWpScripts( moduleName ) {
	return require( resolveFromWpScripts( moduleName ) );
}

module.exports = {
	requireFromWpScripts,
	resolveFromWpScripts,
};
