<?php
/**
 * Plugin Name:       Bitnary Preorder Dates for WooCommerce
 * Plugin URI:        https://preorder.bitnarydigital.com
 * Description:       Sell on pre-order with two independent dates per product or variation: when orders stop and when the release ships.
 * Version:           0.3.2
 * Requires at least: 6.6
 * Tested up to:      7.1
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * WC requires at least: 9.0
 * WC tested up to:   11.1
 * Author:            Davi Souto
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bitnary-preorder-dates-for-woocommerce
 * Domain Path:       /languages
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'PDFW_VERSION', '0.3.2' );
define( 'PDFW_PLUGIN_FILE', __FILE__ );
define( 'PDFW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PDFW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PDFW_MENU_SLUG', 'preorder-dates' );

/*
 * Freemius handles licensing, the Account screen and updates for the paid
 * build, which ships the SDK in freemius/. The WordPress.org version leaves
 * that folder out, so none of this runs there and the free plugin makes no
 * external requests.
 */
if ( is_readable( PDFW_PLUGIN_DIR . 'freemius/start.php' ) ) {

	/**
	 * Returns the Freemius instance, creating it on first use.
	 *
	 * The paid build is this same plugin with a pro/ folder merged in, which
	 * is why is_premium is read off the filesystem rather than hardcoded.
	 *
	 * @return Freemius
	 */
	function pdfw_fs() {
		global $pdfw_fs;

		if ( ! isset( $pdfw_fs ) ) {
			require_once PDFW_PLUGIN_DIR . 'freemius/start.php';

			$pdfw_fs = fs_dynamic_init(
				array(
					'id'                  => '39537',
					'slug'                => 'bitnary-preorder-dates-for-woocommerce',
					'type'                => 'plugin',
					// Public by design: it identifies the product to Freemius from
					// the browser. The secret key is not in this repository and is
					// never needed by the plugin.
					'public_key'          => 'pk_e5aab1ef3cbc31bcd58445d6e4394',
					'is_premium'          => is_readable( PDFW_PLUGIN_DIR . 'pro/load.php' ),
					'has_premium_version' => true,
					'has_paid_plans'      => true,
					'has_addons'          => false,
					'is_org_compliant'    => true,
					'menu'                => array(
						'slug'    => PDFW_MENU_SLUG,
						'parent'  => array( 'slug' => 'woocommerce' ),
						'support' => false,
					),
				)
			);
		}

		return $pdfw_fs;
	}

	pdfw_fs();

	/**
	 * Fires once Freemius is ready, per its own convention.
	 *
	 * @since 0.3.0
	 */
	do_action( 'pdfw_fs_loaded' );
}

require_once PDFW_PLUGIN_DIR . 'includes/class-pdfw-install.php';

register_activation_hook( __FILE__, array( 'PDFW_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PDFW_Install', 'deactivate' ) );

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
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PDFW_PLUGIN_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', PDFW_PLUGIN_FILE, true );
		}
	}
);

add_action( 'plugins_loaded', 'pdfw_bootstrap' );

/**
 * Loads the plugin once WooCommerce is confirmed active, or shows an admin notice otherwise.
 */
function pdfw_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Bitnary Preorder Dates for WooCommerce requires WooCommerce to be installed and active.', 'bitnary-preorder-dates-for-woocommerce' ) . '</p></div>';
			}
		);
		return;
	}

	require_once PDFW_PLUGIN_DIR . 'includes/class-pdfw-data.php';
	require_once PDFW_PLUGIN_DIR . 'includes/class-pdfw-settings.php';
	require_once PDFW_PLUGIN_DIR . 'includes/class-pdfw-product-fields.php';
	require_once PDFW_PLUGIN_DIR . 'includes/class-pdfw-variation-fields.php';
	require_once PDFW_PLUGIN_DIR . 'includes/class-pdfw-purchasability.php';
	require_once PDFW_PLUGIN_DIR . 'includes/class-pdfw-frontend.php';
	require_once PDFW_PLUGIN_DIR . 'includes/class-pdfw-order-meta.php';
	require_once PDFW_PLUGIN_DIR . 'includes/class-pdfw-cron.php';
	require_once PDFW_PLUGIN_DIR . 'includes/class-pdfw-admin-page.php';

	add_action( 'woocommerce_after_register_post_type', array( 'PDFW_Install', 'migrate_legacy_prefix' ) );

	PDFW_Settings::init();
	PDFW_Product_Fields::init();
	PDFW_Variation_Fields::init();
	PDFW_Purchasability::init();
	PDFW_Frontend::init();
	PDFW_Order_Meta::init();
	PDFW_Cron::init();
	PDFW_Admin_Page::init();

	// The paid build of this plugin ships an extra pro/ folder next to these
	// files. It is absent from the version on WordPress.org, so this does
	// nothing there.
	if ( is_readable( PDFW_PLUGIN_DIR . 'pro/load.php' ) ) {
		require_once PDFW_PLUGIN_DIR . 'pro/load.php';
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
	do_action( 'pdfw_loaded' );
}
