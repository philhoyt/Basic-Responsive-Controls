<?php
/**
 * Plugin Name: PH Basic Responsive Controls
 * Plugin URI:  https://github.com/philhoyt/Basic-Responsive-Controls
 * Description: Responsive typography controls for the WordPress block editor.
 * Version:     1.0.0
 * Author:      philhoyt
 * Author URI:  https://philhoyt.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ph-basic-responsive-controls
 *
 * @package ph-basic-responsive-controls
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/render.php';

/**
 * Register responsive attributes on the PHP side so they persist in saved
 * post content and are available to the render_block filter.
 */
add_filter(
	'block_type_metadata',
	function ( $metadata ) {
		$responsive_blocks = array( 'core/heading' );

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

		$asset = require $asset_file;

		wp_enqueue_script(
			'ph-basic-responsive-controls-editor',
			plugins_url( 'build/index.js', __FILE__ ),
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'ph-basic-responsive-controls-editor',
			plugins_url( 'build/index.css', __FILE__ ),
			array(),
			$asset['version']
		);
	}
);
