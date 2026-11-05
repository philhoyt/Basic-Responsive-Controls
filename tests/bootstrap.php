<?php
/**
 * PHPUnit bootstrap.
 *
 * The migration transform is pure PHP, so these tests run without a
 * WordPress install. ABSPATH is defined only to satisfy the direct-access
 * guard at the top of the plugin files.
 *
 * @package ph-basic-responsive-controls
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once dirname( __DIR__ ) . '/includes/migrate.php';
