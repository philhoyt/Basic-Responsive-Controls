<?php
/**
 * Migration of legacy tablet/mobile attributes to WordPress 7.1 responsive
 * style states (`style["@tablet"]` / `style["@mobile"]`).
 *
 * The transform functions in this file are pure: they take parsed block
 * arrays and return new ones without calling WordPress. That keeps them
 * unit-testable without a WordPress bootstrap. Everything that touches
 * WordPress (settings, database, posts) lives further down and is only
 * called at runtime.
 *
 * @package ph-basic-responsive-controls
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block names the legacy controls were registered on.
 *
 * @return string[] Block names.
 */
function ph_brc_migratable_block_names(): array {
	return array( 'core/heading', 'core/paragraph' );
}

/**
 * Map of core typography property to the legacy tablet/mobile attribute keys.
 *
 * @return array<string, array{0: string, 1: string}> Property => [ tablet key, mobile key ].
 */
function ph_brc_legacy_property_map(): array {
	return array(
		'fontSize'      => array( 'tabletFontSize', 'mobileFontSize' ),
		'lineHeight'    => array( 'tabletLineHeight', 'mobileLineHeight' ),
		'letterSpacing' => array( 'tabletLetterSpacing', 'mobileLetterSpacing' ),
		'textAlign'     => array( 'tabletTextAlign', 'mobileTextAlign' ),
	);
}

/**
 * All legacy attribute keys that carry a responsive value.
 *
 * @return string[] Attribute keys.
 */
function ph_brc_legacy_attribute_keys(): array {
	$keys = array();
	foreach ( ph_brc_legacy_property_map() as $pair ) {
		$keys[] = $pair[0];
		$keys[] = $pair[1];
	}
	return $keys;
}

/**
 * Read a non-empty string value from an array, or null.
 *
 * @param array  $source Array to read from.
 * @param string $key    Key to read.
 * @return string|null Trimmed value or null when missing or empty.
 */
function ph_brc_non_empty_string( array $source, string $key ): ?string {
	$value = $source[ $key ] ?? null;
	if ( ! is_string( $value ) ) {
		return null;
	}
	$value = trim( $value );
	return '' === $value ? null : $value;
}

/**
 * Convert a legacy font size value to the form core stores in style states.
 *
 * A registered preset slug becomes `var:preset|font-size|{slug}`, which is
 * what the core Typography panel writes and what `states.php` resolves to
 * `var(--wp--preset--font-size--{slug})`. Values already in `var:preset|` or
 * `var(` form, and any raw CSS value, are returned unchanged.
 *
 * @param string   $value Legacy font size value (slug or raw CSS).
 * @param string[] $slugs Registered font size preset slugs.
 * @return string Converted value.
 */
function ph_brc_convert_font_size_value( string $value, array $slugs ): string {
	if ( 0 === strpos( $value, 'var:preset|' ) || 0 === strpos( $value, 'var(' ) ) {
		return $value;
	}
	if ( in_array( $value, $slugs, true ) ) {
		return 'var:preset|font-size|' . $value;
	}
	return $value;
}

/**
 * Migrate one parsed block (and its inner blocks) from legacy attributes to
 * core responsive style states.
 *
 * Conflict rules per property (`coreT` / `coreM` are existing core values,
 * `legT` / `legM` the legacy attributes):
 * - `@tablet` = coreT if set, else legT if set, else untouched.
 * - `@mobile` = coreM if set, else legM if set, else legT if set, else untouched.
 *
 * The final `legT` fallback for mobile preserves the legacy cascade: the old
 * tablet rule used `max-width: 782px`, which included phones, whereas core's
 * `@tablet` state only covers `480px < width <= 782px`.
 *
 * @param array    $block           Parsed block (blockName, attrs, innerBlocks, ...).
 * @param string[] $font_size_slugs Registered font size preset slugs.
 * @return array{block: array, changed: int} Migrated block and number of blocks changed.
 */
function ph_brc_migrate_block( array $block, array $font_size_slugs ): array {
	$changed = 0;

	if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
		foreach ( $block['innerBlocks'] as $index => $inner_block ) {
			if ( ! is_array( $inner_block ) ) {
				continue;
			}
			$result                         = ph_brc_migrate_block( $inner_block, $font_size_slugs );
			$block['innerBlocks'][ $index ] = $result['block'];
			$changed                       += $result['changed'];
		}
	}

	if ( ! in_array( $block['blockName'] ?? '', ph_brc_migratable_block_names(), true ) ) {
		return array(
			'block'   => $block,
			'changed' => $changed,
		);
	}

	$attrs = $block['attrs'] ?? array();
	if ( ! is_array( $attrs ) ) {
		return array(
			'block'   => $block,
			'changed' => $changed,
		);
	}

	$has_legacy_value = false;
	foreach ( ph_brc_legacy_attribute_keys() as $key ) {
		if ( null !== ph_brc_non_empty_string( $attrs, $key ) ) {
			$has_legacy_value = true;
			break;
		}
	}

	if ( ! $has_legacy_value ) {
		return array(
			'block'   => $block,
			'changed' => $changed,
		);
	}

	$style = isset( $attrs['style'] ) && is_array( $attrs['style'] ) ? $attrs['style'] : array();

	foreach ( ph_brc_legacy_property_map() as $property => $keys ) {
		list( $tablet_key, $mobile_key ) = $keys;

		$legacy_tablet = ph_brc_non_empty_string( $attrs, $tablet_key );
		$legacy_mobile = ph_brc_non_empty_string( $attrs, $mobile_key );

		if ( 'fontSize' === $property ) {
			$legacy_tablet = null === $legacy_tablet ? null : ph_brc_convert_font_size_value( $legacy_tablet, $font_size_slugs );
			$legacy_mobile = null === $legacy_mobile ? null : ph_brc_convert_font_size_value( $legacy_mobile, $font_size_slugs );
		}

		$core_tablet = ph_brc_non_empty_string( $style['@tablet']['typography'] ?? array(), $property );
		$core_mobile = ph_brc_non_empty_string( $style['@mobile']['typography'] ?? array(), $property );

		if ( null === $core_tablet && null !== $legacy_tablet ) {
			$style['@tablet']['typography'][ $property ] = $legacy_tablet;
		}

		if ( null === $core_mobile ) {
			if ( null !== $legacy_mobile ) {
				$style['@mobile']['typography'][ $property ] = $legacy_mobile;
			} elseif ( null !== $legacy_tablet ) {
				$style['@mobile']['typography'][ $property ] = $legacy_tablet;
			}
		}
	}

	foreach ( ph_brc_legacy_attribute_keys() as $key ) {
		unset( $attrs[ $key ] );
	}
	unset( $attrs['blockId'] );

	if ( empty( $style ) ) {
		unset( $attrs['style'] );
	} else {
		$attrs['style'] = $style;
	}

	$block['attrs'] = $attrs;
	++$changed;

	return array(
		'block'   => $block,
		'changed' => $changed,
	);
}

/**
 * Migrate a list of parsed blocks.
 *
 * @param array    $blocks          Parsed blocks, as returned by parse_blocks().
 * @param string[] $font_size_slugs Registered font size preset slugs.
 * @return array{blocks: array, changed: int} Migrated blocks and number of blocks changed.
 */
function ph_brc_migrate_blocks( array $blocks, array $font_size_slugs ): array {
	$changed = 0;

	foreach ( $blocks as $index => $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}
		$result           = ph_brc_migrate_block( $block, $font_size_slugs );
		$blocks[ $index ] = $result['block'];
		$changed         += $result['changed'];
	}

	return array(
		'blocks'  => $blocks,
		'changed' => $changed,
	);
}

/**
 * Registered font size preset slugs from theme.json (default, theme, custom).
 *
 * Mirrors the lookup in includes/render.php so slug resolution matches the
 * legacy frontend output.
 *
 * @return string[] Preset slugs.
 */
function ph_brc_get_font_size_slugs(): array {
	$settings  = wp_get_global_settings( array( 'typography', 'fontSizes' ) );
	$all_sizes = array_merge(
		$settings['default'] ?? array(),
		$settings['theme'] ?? array(),
		$settings['custom'] ?? array()
	);

	$slugs = array();
	foreach ( $all_sizes as $preset ) {
		if ( is_array( $preset ) && ! empty( $preset['slug'] ) && is_string( $preset['slug'] ) ) {
			$slugs[] = $preset['slug'];
		}
	}

	return array_values( array_unique( $slugs ) );
}
