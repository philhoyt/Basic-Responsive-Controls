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

/**
 * Whether the running WordPress renders responsive style states natively.
 *
 * Kept separate from ph_brc_is_legacy_ui_enabled() so the migration notice
 * is unaffected by the `ph_brc_legacy_ui_enabled` filter.
 *
 * @return bool True on WordPress 7.1 or later.
 */
function ph_brc_core_has_responsive_styles(): bool {
	return version_compare( $GLOBALS['wp_version'], '7.1', '>=' );
}

/**
 * Render the migration and result notices on the Dashboard and Plugins screens.
 */
function ph_brc_render_admin_notices(): void {
	if ( ! ph_brc_core_has_responsive_styles() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) ) {
		return;
	}

	ph_brc_render_result_notice();
	ph_brc_render_migration_notice();
}

/**
 * Render the one-shot notice describing the last migration run.
 */
function ph_brc_render_result_notice(): void {
	$result = get_transient( 'ph_brc_migration_result' );
	if ( ! is_array( $result ) ) {
		return;
	}
	delete_transient( 'ph_brc_migration_result' );

	$posts_updated   = (int) ( $result['posts_updated'] ?? 0 );
	$blocks_migrated = (int) ( $result['blocks_migrated'] ?? 0 );
	$skipped         = (int) ( $result['skipped'] ?? 0 );
	$remaining       = (int) ( $result['remaining'] ?? 0 );
	$errors          = is_array( $result['errors'] ?? null ) ? $result['errors'] : array();
	$is_complete     = 0 === $remaining && empty( $errors );

	$message = '<p><strong>' . esc_html__( 'Basic Responsive Controls', 'ph-basic-responsive-controls' ) . '</strong> — ' . sprintf(
		/* translators: 1: number of blocks, 2: number of posts, 3: number of skipped posts, 4: number of posts still holding legacy values */
		esc_html__( 'migrated %1$s blocks across %2$s posts. %3$s matching posts needed no changes. %4$s posts still contain legacy values.', 'ph-basic-responsive-controls' ),
		number_format_i18n( $blocks_migrated ),
		number_format_i18n( $posts_updated ),
		number_format_i18n( $skipped ),
		number_format_i18n( $remaining )
	) . '</p>';

	if ( ! empty( $errors ) ) {
		$message .= '<p>' . esc_html__( 'The following posts could not be updated:', 'ph-basic-responsive-controls' ) . '</p><ul>';
		foreach ( $errors as $post_id => $error_message ) {
			$message .= '<li>#' . (int) $post_id . ': ' . esc_html( (string) $error_message ) . '</li>';
		}
		$message .= '</ul>';
	}

	if ( $is_complete ) {
		$message .= '<p>' . sprintf(
			/* translators: %s: URL of the Plugins screen */
			wp_kses( __( 'Everything has been migrated to WordPress\'s native responsive styles. Basic Responsive Controls is now safe to <a href="%s">deactivate</a>.', 'ph-basic-responsive-controls' ), array( 'a' => array( 'href' => array() ) ) ),
			esc_url( admin_url( 'plugins.php' ) )
		) . '</p>';
	}

	wp_admin_notice(
		$message,
		array(
			'type'           => $is_complete ? 'success' : 'warning',
			'dismissible'    => true,
			'paragraph_wrap' => false,
		)
	);
}

/**
 * Render the notice offering to migrate legacy content.
 *
 * The notice is echoed directly rather than through wp_admin_notice() because
 * that helper runs the message through wp_kses_post(), which strips the form
 * and input elements the "Migrate now" button needs.
 */
function ph_brc_render_migration_notice(): void {
	$count = ph_brc_count_legacy_posts();
	if ( $count < 1 ) {
		return;
	}

	$count_text = sprintf(
		/* translators: %s: number of posts */
		_n(
			'%s post still uses this plugin\'s legacy tablet/mobile attributes.',
			'%s posts still use this plugin\'s legacy tablet/mobile attributes.',
			$count,
			'ph-basic-responsive-controls'
		),
		number_format_i18n( $count )
	);

	$button_text = sprintf(
		/* translators: %s: number of posts */
		_n( 'Migrate %s post now', 'Migrate %s posts now', $count, 'ph-basic-responsive-controls' ),
		number_format_i18n( $count )
	);
	?>
	<div class="notice notice-warning">
		<p>
			<strong><?php esc_html_e( 'Basic Responsive Controls', 'ph-basic-responsive-controls' ); ?></strong>
			— <?php echo esc_html( $count_text ); ?>
			<?php esc_html_e( 'WordPress 7.1 provides these controls natively. Migrating rewrites post content into WordPress\'s own responsive styles, so back up your database first. Migration is one-way, and the migrated content only renders responsively on WordPress 7.1 or later.', 'ph-basic-responsive-controls' ); ?>
		</p>
		<?php if ( current_user_can( 'unfiltered_html' ) ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ph_brc_migrate" />
				<?php wp_nonce_field( 'ph_brc_migrate' ); ?>
				<p><?php submit_button( $button_text, 'primary', 'submit', false ); ?></p>
			</form>
		<?php else : ?>
			<p><?php esc_html_e( 'A super admin (a user with the unfiltered_html capability) must run the migration on this site.', 'ph-basic-responsive-controls' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

add_action( 'admin_notices', 'ph_brc_render_admin_notices' );
add_action( 'admin_post_ph_brc_migrate', 'ph_brc_run_migration' );
