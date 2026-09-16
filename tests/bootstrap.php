<?php
/**
 * Test bootstrap.
 *
 * These are unit tests, not integration tests: they run the plugin's own logic
 * against small stand-ins for the WordPress and WooCommerce functions it calls,
 * with no database and no WordPress install. That keeps the suite fast enough
 * to run on every push, and it covers the part of the plugin where the bugs
 * actually live, which is date conversion and the pre-order state machine.
 *
 * Anything that genuinely needs WordPress (the settings screen, the Store API
 * validation) is checked by hand in the WooCommerce test store instead.
 *
 * @package PreorderDatesForWooCommerce
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'PDW_TESTING', true );

require_once __DIR__ . '/stubs/wordpress.php';
require_once __DIR__ . '/stubs/class-wc-product.php';

require_once dirname( __DIR__ ) . '/includes/class-pdw-data.php';
require_once dirname( __DIR__ ) . '/includes/class-pdw-settings.php';
