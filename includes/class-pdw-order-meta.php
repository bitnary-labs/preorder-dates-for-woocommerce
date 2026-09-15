<?php
/**
 * Saves the release date on the order line item, so it shows up in the
 * order admin screen and in WooCommerce's own order emails.
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

class PDW_Order_Meta {

	/**
	 * Internal, hidden meta key holding the raw GMT timestamp (kept for
	 * anything that needs the exact value; hidden from display because
	 * WC_Order_Item::get_formatted_meta_data() hides any key starting with
	 * the default "_" prefix).
	 *
	 * @see https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/includes/class-wc-order-item.php
	 */
	const META_RELEASE_RAW = '_pdw_release_gmt';

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'add_line_item_meta' ), 10, 4 );
	}

	/**
	 * @param WC_Order_Item_Product $item          Order line item being created.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values         Cart item values, including 'data' (WC_Product).
	 * @param WC_Order              $order          Order being created.
	 */
	public static function add_line_item_meta( $item, $cart_item_key, $values, $order ) {
		$product = isset( $values['data'] ) ? $values['data'] : null;

		if ( ! $product instanceof WC_Product || 'open' !== PDW_Data::get_state( $product ) ) {
			return;
		}

		$release = PDW_Data::get_release( $product );
		if ( ! $release ) {
			return;
		}

		// Hidden, machine-readable copy.
		$item->add_meta_data( self::META_RELEASE_RAW, $release, true );

		// Visible copy: WooCommerce shows any order item meta whose key does
		// not start with "_" automatically, in both the admin order screen and
		// order emails, using the key itself as the label.
		$item->add_meta_data( __( 'Ships on', 'preorder-dates-for-woocommerce' ), PDW_Settings::format_date( $release ), true );
	}
}
