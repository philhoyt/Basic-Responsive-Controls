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
 * Resolve a font size value (slug or raw CSS) to a usable CSS value.
 *
 * If the value matches a registered theme font size slug, returns the
 * corresponding CSS custom property. Otherwise returns the value as-is.
 *
 * @param string $value Font size slug or raw CSS value.
 * @return string Resolved CSS value.
 */
function ph_brc_resolve_font_size( string $value ): string {
	$settings  = wp_get_global_settings( [ 'typography', 'fontSizes' ] );
	$all_sizes = array_merge(
		$settings['default'] ?? [],
		$settings['theme']   ?? [],
		$settings['custom']  ?? []
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
 */
add_filter( 'render_block', function ( string $block_content, array $block ): string {
	$responsive_blocks = [
		'core/heading' => [ 'fontSize' ],
	];

	$block_name = $block['blockName'] ?? '';

	if ( ! isset( $responsive_blocks[ $block_name ] ) ) {
		return $block_content;
	}

	$attrs    = $block['attrs'] ?? [];
	$block_id = $attrs['blockId'] ?? '';

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
		$value  = ph_brc_resolve_font_size( $tablet_font_size );
		$style .= '@media(max-width:782px){.phbrc-' . $block_id . '{font-size:' . $value . ' !important;}}';
	}

	if ( ! empty( $mobile_font_size ) ) {
		$value  = ph_brc_resolve_font_size( $mobile_font_size );
		$style .= '@media(max-width:480px){.phbrc-' . $block_id . '{font-size:' . $value . ' !important;}}';
	}

	$style .= '</style>';

	return $style . $block_content;
}, 10, 2 );
