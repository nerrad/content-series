const baseConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...baseConfig,
	setupFilesAfterEnv: [ '<rootDir>/tests/js/setup.js' ],
	testMatch: [
		'<rootDir>/src/**/*.test.ts',
		'<rootDir>/src/**/*.test.tsx',
		'<rootDir>/src/**/*.spec.ts',
		'<rootDir>/src/**/*.spec.tsx',
		'<rootDir>/tests/**/*.test.ts',
		'<rootDir>/tests/**/*.test.tsx',
		'<rootDir>/tests/**/*.spec.ts',
		'<rootDir>/tests/**/*.spec.tsx',
	],
	collectCoverageFrom: [
		'src/**/*.{ts,tsx}',
		'!src/**/*.d.ts',
		'!src/**/*.test.{ts,tsx}',
		'!src/**/*.spec.{ts,tsx}',
	],
	coverageDirectory: '<rootDir>/coverage/js',
};
