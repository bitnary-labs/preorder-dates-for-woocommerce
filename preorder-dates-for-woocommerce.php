<?php
/**
 * Plugin Name:       Preorder Dates for WooCommerce
 * Plugin URI:        https://preorder.bitnarydigital.com
 * Description:       Sell on pre-order with two independent dates per product or variation: when orders stop and when the release ships.
 * Version:           0.2.0
 * Requires at least: 6.6
 * Tested up to:      7.1
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * WC requires at least: 9.0
 * WC tested up to:   11.1
 * Author:            Davi Souto
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       preorder-dates-for-woocommerce
 * Domain Path:       /languages
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'PDW_VERSION', '0.2.0' );
define( 'PDW_PLUGIN_FILE', __FILE__ );
define( 'PDW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PDW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once PDW_PLUGIN_DIR . 'includes/class-pdw-install.php';

register_activation_hook( __FILE__, array( 'PDW_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PDW_Install', 'deactivate' ) );

/**
 * Declares compatibility with WooCommerce features that this plugin was built and tested against.
 *
 * Must run on 'before_woocommerce_init', per WooCommerce's FeaturesUtil contract.
 *
 * @see https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/src/Utilities/FeaturesUtil.php
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PDW_PLUGIN_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', PDW_PLUGIN_FILE, true );
		}
	}
);

add_action( 'plugins_loaded', 'pdw_bootstrap' );

/**
 * Loads the plugin once WooCommerce is confirmed active, or shows an admin notice otherwise.
 */
function pdw_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Preorder Dates for WooCommerce requires WooCommerce to be installed and active.', 'preorder-dates-for-woocommerce' ) . '</p></div>';
			}
		);
		return;
	}

	require_once PDW_PLUGIN_DIR . 'includes/class-pdw-data.php';
	require_once PDW_PLUGIN_DIR . 'includes/class-pdw-settings.php';
	require_once PDW_PLUGIN_DIR . 'includes/class-pdw-product-fields.php';
	require_once PDW_PLUGIN_DIR . 'includes/class-pdw-variation-fields.php';
	require_once PDW_PLUGIN_DIR . 'includes/class-pdw-purchasability.php';
	require_once PDW_PLUGIN_DIR . 'includes/class-pdw-frontend.php';
	require_once PDW_PLUGIN_DIR . 'includes/class-pdw-order-meta.php';
	require_once PDW_PLUGIN_DIR . 'includes/class-pdw-cron.php';
	require_once PDW_PLUGIN_DIR . 'includes/class-pdw-admin-page.php';

	PDW_Settings::init();
	PDW_Product_Fields::init();
	PDW_Variation_Fields::init();
	PDW_Purchasability::init();
	PDW_Frontend::init();
	PDW_Order_Meta::init();
	PDW_Cron::init();
	PDW_Admin_Page::init();

	// The paid build of this plugin ships an extra pro/ folder next to these
	// files. It is absent from the version on WordPress.org, so this does
	// nothing there.
	if ( is_readable( PDW_PLUGIN_DIR . 'pro/load.php' ) ) {
		require_once PDW_PLUGIN_DIR . 'pro/load.php';
	}

	/**
	 * Fires once every class of the free plugin is loaded and hooked.
	 *
	 * This is the extension point the Pro add-on waits on. It runs inside
	 * 'plugins_loaded', so anything hooking it is still early enough to
	 * register its own filters on WooCommerce.
	 *
	 * @since 0.2.0
	 */
	do_action( 'pdw_loaded' );
}
