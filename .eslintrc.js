module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	globals: {
		contentSeriesQuickEditData: 'readonly',
		inlineEditPost: 'readonly',
		jQuery: 'readonly',
	},
	rules: {
		// Allow console statements (used for debugging/warnings)
		'no-console': 'off',
		// WordPress uses underscores in variable names
		camelcase: 'off',
	},
};
