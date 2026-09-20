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

	/*
	 * Any legacy key present, even with an empty value, marks the block for
	 * rewriting. Empty keys carry no style but still match the discovery
	 * query, so they must be stripped for the remaining count to reach zero.
	 * A block carrying only `blockId` is left alone: it is not discoverable
	 * and rewriting it would touch posts for no visible change.
	 */
	$has_legacy_key = false;
	foreach ( ph_brc_legacy_attribute_keys() as $key ) {
		if ( array_key_exists( $key, $attrs ) ) {
			$has_legacy_key = true;
			break;
		}
	}

	if ( ! $has_legacy_key ) {
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

/**
 * SQL WHERE fragment matching posts whose content carries legacy attributes.
 *
 * Patterns include the surrounding quote and colon (`"tabletFontSize":"`) so
 * prose that merely mentions an attribute name does not match.
 *
 * @param int[] $exclude_ids Post IDs to exclude from the match.
 * @return string Prepared WHERE clause (without the leading WHERE keyword).
 */
function ph_brc_legacy_where_sql( array $exclude_ids = array() ): string {
	global $wpdb;

	$likes = array();
	$args  = array();
	foreach ( ph_brc_legacy_attribute_keys() as $key ) {
		$likes[] = 'post_content LIKE %s';
		$args[]  = '%' . $wpdb->esc_like( '"' . $key . '":"' ) . '%';
	}

	$where = '(' . implode( ' OR ', $likes ) . ")
		AND post_type <> 'revision'
		AND post_status NOT IN ( 'trash', 'auto-draft' )";

	$exclude_ids = array_filter( array_map( 'intval', $exclude_ids ) );
	if ( ! empty( $exclude_ids ) ) {
		$where .= ' AND ID NOT IN (' . implode( ',', array_fill( 0, count( $exclude_ids ), '%d' ) ) . ')';
		$args   = array_merge( $args, $exclude_ids );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders are built above and every value is passed through prepare().
	return $wpdb->prepare( $where, $args );
}

/**
 * Count posts that still carry legacy attributes.
 *
 * The result is cached in a transient for 12 hours; the migration runner
 * deletes the transient before it starts.
 *
 * @param bool $force Bypass and refresh the cached count.
 * @return int Number of matching posts.
 */
function ph_brc_count_legacy_posts( bool $force = false ): int {
	global $wpdb;

	if ( ! $force ) {
		$cached = get_transient( 'ph_brc_legacy_count' );
		if ( false !== $cached ) {
			return (int) $cached;
		}
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- one-off LIKE scan; the WHERE clause is prepared and the count is cached in a transient.
	$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE " . ph_brc_legacy_where_sql() );

	set_transient( 'ph_brc_legacy_count', $count, 12 * HOUR_IN_SECONDS );

	return $count;
}

/**
 * IDs of posts that still carry legacy attributes.
 *
 * @param int   $limit       Maximum number of IDs to return.
 * @param int[] $exclude_ids Post IDs already handled in the current run.
 * @return int[] Post IDs.
 */
function ph_brc_get_legacy_post_ids( int $limit, array $exclude_ids = array() ): array {
	global $wpdb;

	/*
	 * The WHERE clause is already prepared (it contains literal LIKE
	 * wildcards), so the LIMIT is prepared separately rather than running the
	 * whole statement through prepare() a second time.
	 */
	$where = ph_brc_legacy_where_sql( $exclude_ids );
	$limit = $wpdb->prepare( 'LIMIT %d', max( 1, $limit ) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- migration batch query; both fragments are prepared above.
	$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE {$where} ORDER BY ID ASC {$limit}" );

	return array_map( 'intval', (array) $ids );
}

/**
 * Migrate a single post's content.
 *
 * @param int      $post_id Post ID.
 * @param string[] $slugs   Registered font size preset slugs.
 * @return int|WP_Error Number of blocks migrated (0 when nothing changed), or an error.
 */
function ph_brc_migrate_post( int $post_id, array $slugs ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return new WP_Error(
			'ph_brc_missing_post',
			sprintf(
				/* translators: %d: post ID */
				__( 'Post %d could not be loaded.', 'ph-basic-responsive-controls' ),
				$post_id
			)
		);
	}

	$result = ph_brc_migrate_blocks( parse_blocks( $post->post_content ), $slugs );
	if ( 0 === $result['changed'] ) {
		return 0;
	}

	$updated = wp_update_post(
		wp_slash(
			array(
				'ID'           => $post_id,
				'post_content' => serialize_blocks( $result['blocks'] ),
			)
		),
		true
	);

	if ( is_wp_error( $updated ) ) {
		return $updated;
	}

	if ( 0 === $updated ) {
		return new WP_Error(
			'ph_brc_update_failed',
			sprintf(
				/* translators: %d: post ID */
				__( 'Post %d could not be updated.', 'ph-basic-responsive-controls' ),
				$post_id
			)
		);
	}

	/**
	 * Fires after a post's legacy responsive attributes were migrated.
	 *
	 * @param int $post_id        Post ID.
	 * @param int $blocks_changed Number of blocks rewritten in the post.
	 */
	do_action( 'ph_brc_migrated_post', $post_id, $result['changed'] );

	return $result['changed'];
}

/**
 * Run the migration for as many posts as fit in the time budget, then
 * redirect back to the referring admin screen.
 *
 * Hooked to `admin_post_ph_brc_migrate`. Requires `manage_options` and
 * `unfiltered_html` so content is written back without KSES filtering.
 */
function ph_brc_run_migration(): void {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'unfiltered_html' ) ) {
		wp_die(
			esc_html__( 'You do not have permission to run this migration.', 'ph-basic-responsive-controls' ),
			'',
			array( 'response' => 403 )
		);
	}

	check_admin_referer( 'ph_brc_migrate' );

	delete_transient( 'ph_brc_legacy_count' );

	$slugs     = ph_brc_get_font_size_slugs();
	$started   = microtime( true );
	$processed = array();
	$result    = array(
		'posts_updated'   => 0,
		'blocks_migrated' => 0,
		'skipped'         => 0,
		'errors'          => array(),
		'remaining'       => 0,
	);

	do {
		$ids = ph_brc_get_legacy_post_ids( 25, $processed );
		if ( empty( $ids ) ) {
			break;
		}

		foreach ( $ids as $post_id ) {
			$processed[] = $post_id;
			$migrated    = ph_brc_migrate_post( $post_id, $slugs );

			if ( is_wp_error( $migrated ) ) {
				$result['errors'][ $post_id ] = $migrated->get_error_message();
			} elseif ( 0 === $migrated ) {
				++$result['skipped'];
			} else {
				++$result['posts_updated'];
				$result['blocks_migrated'] += $migrated;
			}
		}
	} while ( ( microtime( true ) - $started ) < 20 );

	$result['remaining'] = ph_brc_count_legacy_posts( true );

	set_transient( 'ph_brc_migration_result', $result, 5 * MINUTE_IN_SECONDS );

	$referer = wp_get_referer();
	wp_safe_redirect( $referer ? $referer : admin_url( 'plugins.php' ) );
	exit;
}
