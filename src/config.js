/**
 * Blocks and controls registered for responsive typography overrides.
 *
 * Adding a new block requires a matching entry in includes/render.php
 * ($responsive_blocks array) — the two must be kept in sync manually.
 *
 * @type {Object.<string, {controls: string[]}>}
 */
export const RESPONSIVE_BLOCKS = {
	'core/heading': {
		controls: [ 'fontSize' ],
	},
};
