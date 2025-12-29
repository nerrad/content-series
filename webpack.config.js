const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		// Editor sidebar panel
		'sidebar/index': path.resolve( __dirname, 'src/sidebar/index.ts' ),

		// Blocks
		'blocks/series-post-list/index': path.resolve( __dirname, 'src/blocks/series-post-list/index.ts' ),
		'blocks/series-navigation/index': path.resolve( __dirname, 'src/blocks/series-navigation/index.ts' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
	resolve: {
		...defaultConfig.resolve,
		extensions: [ '.ts', '.tsx', '.js', '.jsx', '.json' ],
	},
};
