<?php
/**
 * Plugin Name: Basic Responsive Controls
 * Plugin URI:  https://github.com/philhoyt/Basic-Responsive-Controls
 * Description: Sunset. WordPress 7.1 provides responsive typography natively; this release migrates existing content to core and then gets out of the way.
 * Version:     1.1.0
 * Author:      philhoyt
 * Author URI:  https://philhoyt.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ph-basic-responsive-controls
 *
 * @package ph-basic-responsive-controls
 */

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Updates are delivered from GitHub releases via Plugin Update Checker.
require_once __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php';

$basic_responsive_controls_update_checker     = PucFactory::buildUpdateChecker(
	'https://github.com/philhoyt/Basic-Responsive-Controls/',
	__FILE__,
	'basic-responsive-controls'
);
$basic_responsive_controls_update_checker_api = $basic_responsive_controls_update_checker->getVcsApi();
if ( method_exists( $basic_responsive_controls_update_checker_api, 'enableReleaseAssets' ) ) {
	// Only a .zip asset counts, and a release without one is skipped. The
	// default PREFER_RELEASE_ASSETS falls back to GitHub's source archive,
	// which installs as <Repo>-<tag>/ and breaks the plugin directory.
	$basic_responsive_controls_update_checker_api->enableReleaseAssets( '/\.zip$/', $basic_responsive_controls_update_checker_api::REQUIRE_RELEASE_ASSETS );
}

require_once __DIR__ . '/includes/migrate.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/render.php';

/**
 * Register responsive attributes on the PHP side so they persist in saved
 * post content and are available to the render_block filter.
 */
add_filter(
	'block_type_metadata',
	function ( $metadata ) {
		$responsive_blocks = array( 'core/heading', 'core/paragraph' );

		if ( ! in_array( $metadata['name'] ?? '', $responsive_blocks, true ) ) {
			return $metadata;
		}

		$metadata['attributes']['blockId']             = array(
			'type'    => 'string',
			'default' => '',
		);
		$metadata['attributes']['tabletFontSize']      = array(
			'type'    => 'string',
			'default' => '',
		);
		$metadata['attributes']['mobileFontSize']      = array(
			'type'    => 'string',
			'default' => '',
		);
		$metadata['attributes']['tabletTextAlign']     = array(
			'type'    => 'string',
			'default' => '',
		);
		$metadata['attributes']['mobileTextAlign']     = array(
			'type'    => 'string',
			'default' => '',
		);
		$metadata['attributes']['tabletLineHeight']    = array(
			'type'    => 'string',
			'default' => '',
		);
		$metadata['attributes']['mobileLineHeight']    = array(
			'type'    => 'string',
			'default' => '',
		);
		$metadata['attributes']['tabletLetterSpacing'] = array(
			'type'    => 'string',
			'default' => '',
		);
		$metadata['attributes']['mobileLetterSpacing'] = array(
			'type'    => 'string',
			'default' => '',
		);

		return $metadata;
	}
);

/**
 * Enqueue block editor assets.
 */
add_action(
	'enqueue_block_editor_assets',
	function () {
		$asset_file = __DIR__ . '/build/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset     = require $asset_file;
		$legacy_ui = ph_brc_is_legacy_ui_enabled();

		// The script is always loaded: it registers the legacy attributes so
		// unmigrated content is not stripped on re-save. The editor UI inside
		// it is only registered when the legacy UI flag is true.
		wp_enqueue_script(
			'ph-basic-responsive-controls-editor',
			plugins_url( 'build/index.js', __FILE__ ),
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_add_inline_script(
			'ph-basic-responsive-controls-editor',
			'window.phBrc = ' . wp_json_encode( array( 'legacyUi' => $legacy_ui ) ) . ';',
			'before'
		);

		if ( ! $legacy_ui ) {
			return;
		}

		wp_enqueue_style(
			'ph-basic-responsive-controls-editor',
			plugins_url( 'build/index.css', __FILE__ ),
			array(),
			$asset['version']
		);
	}
);
