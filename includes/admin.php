<?php
/**
 * Admin-side helpers: WordPress version gate for the legacy editor UI.
 *
 * @package ph-basic-responsive-controls
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the legacy tablet/mobile editor controls should be loaded.
 *
 * WordPress 7.1 ships native responsive style states (`style["@tablet"]`
 * and `style["@mobile"]`) for every core block support, which covers every
 * control this plugin adds. On 7.1+ the legacy UI is disabled so it does not
 * hide core's own responsive controls.
 *
 * Pre-release version strings such as `7.1-RC4` compare as lower than `7.1`
 * and therefore still get the legacy UI. Use the filter to override.
 *
 * @return bool True when the legacy editor UI should be registered.
 */
function ph_brc_is_legacy_ui_enabled(): bool {
	$enabled = version_compare( $GLOBALS['wp_version'], '7.1', '<' );

	/**
	 * Filters whether the legacy tablet/mobile editor UI is enabled.
	 *
	 * @param bool $enabled True on WordPress < 7.1, false otherwise.
	 */
	return (bool) apply_filters( 'ph_brc_legacy_ui_enabled', $enabled );
}

add_action( 'admin_post_ph_brc_migrate', 'ph_brc_run_migration' );
