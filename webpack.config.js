const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		// Editor sidebar panel
		'sidebar/index': path.resolve( __dirname, 'src/sidebar/index.ts' ),

		// Blocks
		'blocks/series-post-list/index': path.resolve(
			__dirname,
			'src/blocks/series-post-list/index.ts'
		),
		'blocks/series-navigation/index': path.resolve(
			__dirname,
			'src/blocks/series-navigation/index.tsx'
		),
		'blocks/series-navigation-link/index': path.resolve(
			__dirname,
			'src/blocks/series-navigation-link/index.ts'
		),
		'blocks/series-title/index': path.resolve(
			__dirname,
			'src/blocks/series-title/index.ts'
		),
		'blocks/series-icon/index': path.resolve(
			__dirname,
			'src/blocks/series-icon/index.ts'
		),

		// Block bindings
		'bindings/index': path.resolve( __dirname, 'src/bindings/index.ts' ),

		// Block variations
		'variations/index': path.resolve(
			__dirname,
			'src/variations/index.ts'
		),
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
