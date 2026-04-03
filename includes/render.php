<?php
/**
 * Frontend render filter for responsive typography controls.
 *
 * @package ph-basic-responsive-controls
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize a CSS value for safe output inside a <style> tag.
 *
 * Strips characters that could break out of the style block or inject
 * markup. Allows the characters needed for valid CSS values including
 * custom properties, clamp(), rem, em, px, and percentages.
 *
 * @param string $value Raw CSS value.
 * @return string Sanitized CSS value.
 */
function ph_brc_sanitize_css_value( string $value ): string {
	return preg_replace( '/[^a-zA-Z0-9\s\-_()\.,:%\/]/', '', $value );
}

/**
 * Resolve a font size value (slug or raw CSS) to a usable CSS value.
 *
 * If the value matches a registered theme font size slug, returns the
 * corresponding CSS custom property. Otherwise returns the value as-is.
 *
 * @param string $value Font size slug or raw CSS value.
 * @return string Resolved CSS value.
 */
function ph_brc_resolve_font_size( string $value ): string {
	$settings  = wp_get_global_settings( array( 'typography', 'fontSizes' ) );
	$all_sizes = array_merge(
		$settings['default'] ?? array(),
		$settings['theme'] ?? array(),
		$settings['custom'] ?? array()
	);

	foreach ( $all_sizes as $preset ) {
		if ( ( $preset['slug'] ?? '' ) === $value ) {
			return 'var(--wp--preset--font-size--' . $preset['slug'] . ')';
		}
	}

	return $value;
}

/**
 * Inject scoped responsive font size styles into matching block output.
 *
 * Supported blocks are listed below. Adding a new block also requires a
 * matching entry in src/config.js (RESPONSIVE_BLOCKS) — keep them in sync.
 */
add_filter(
	'render_block',
	function ( string $block_content, array $block ): string {
		$responsive_blocks = array(
			'core/heading' => array( 'fontSize' ),
		);

		$block_name = $block['blockName'] ?? '';

		if ( ! isset( $responsive_blocks[ $block_name ] ) ) {
			return $block_content;
		}

		$attrs    = $block['attrs'] ?? array();
		$block_id = sanitize_html_class( $attrs['blockId'] ?? '' );

		if ( empty( $block_id ) ) {
			return $block_content;
		}

		$tablet_font_size = $attrs['tabletFontSize'] ?? '';
		$mobile_font_size = $attrs['mobileFontSize'] ?? '';

		if ( empty( $tablet_font_size ) && empty( $mobile_font_size ) ) {
			return $block_content;
		}

		// Add scoping class to the block's outermost wrapper element.
		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			$processor->add_class( 'phbrc-' . $block_id );
			$block_content = $processor->get_updated_html();
		}

		// Build scoped style tag. Tablet rule must come before mobile so the
		// cascade works correctly: mobile (≤480px) overrides tablet (≤782px).
		$style = '<style>';

		if ( ! empty( $tablet_font_size ) ) {
			$value  = ph_brc_sanitize_css_value( ph_brc_resolve_font_size( $tablet_font_size ) );
			$style .= '@media(max-width:782px){.phbrc-' . $block_id . '{font-size:' . $value . ' !important;}}';
		}

		if ( ! empty( $mobile_font_size ) ) {
			$value  = ph_brc_sanitize_css_value( ph_brc_resolve_font_size( $mobile_font_size ) );
			$style .= '@media(max-width:480px){.phbrc-' . $block_id . '{font-size:' . $value . ' !important;}}';
		}

		$style .= '</style>';

		return $style . $block_content;
	},
	10,
	2
);
