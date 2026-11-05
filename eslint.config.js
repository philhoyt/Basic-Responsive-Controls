/**
 * ESLint flat config (ESLint 10, `@wordpress/scripts` 32+). Replaces the
 * legacy .eslintrc.js, which flat config no longer reads.
 */
/* eslint-disable import/no-extraneous-dependencies -- transitive deps of @wordpress/scripts */
const wpPlugin = require( '@wordpress/eslint-plugin' );
const globals = require( 'globals' );
/* eslint-enable import/no-extraneous-dependencies */

module.exports = [
	{
		ignores: [ 'build/**', 'lib/**', 'vendor/**', 'node_modules/**' ],
	},
	{
		// ESLint flat config discovers only *.js, *.cjs, *.mjs on its own.
		// Name every other extension here or the rules below never see it.
		files: [ '**/*.jsx', '**/*.ts', '**/*.tsx' ],
	},
	...wpPlugin.configs.recommended,
	{
		languageOptions: {
			globals: {
				...globals.browser,
			},
		},
	},
];
