<?php
/**
 * Front-end label, button text and cart/checkout line for pre-order products.
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Everything the shopper sees: the pre-order label, the button text, and the
 * release date line in the cart and checkout.
 */
class PDW_Frontend {

	/**
	 * Registers hooks.
	 */
	public static function init() {
		// Priority 25: after the price (10) and short excerpt (20), before the
		// add-to-cart form (30), on both single product pages and archive/shop
		// loops (both templates call woocommerce_template_single_excerpt() etc.
		// via 'woocommerce_single_product_summary', which also fires per-product
		// on some loop templates; 'woocommerce_after_shop_loop_item_title' is the
		// dedicated loop hook used by core themes).
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_label' ), 25 );
		add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'render_label' ), 5 );

		// Only on a variable product's own page: swaps the add-to-cart button
		// text to match the pre-order state of the variation currently selected.
		// wc-add-to-cart-variation.js (the script that fires 'found_variation'/
		// 'reset_data') does not touch button text itself, only CSS classes.
		// @see https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/client/legacy/js/frontend/add-to-cart-variation.js
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_variation_script' ) );

		add_filter( 'woocommerce_product_single_add_to_cart_text', array( __CLASS__, 'button_text' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'button_text' ), 10, 2 );

		// Covers the classic cart/checkout templates AND the cart/checkout
		// blocks: CartItemSchema::get_item_data() (Store API) applies this same
		// 'woocommerce_get_item_data' filter when building each cart item's
		// "item_data" field, which is what the blocks render.
		// @see https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/src/StoreApi/Schemas/V1/CartItemSchema.php
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'add_cart_item_data' ), 10, 2 );
	}

	/**
	 * Outputs the pre-order label (open) or the closed message, for whichever
	 * product is currently in scope (single product page or loop item).
	 *
	 * A variable product only ever carries pre-order meta on its variations,
	 * never on itself, so it is summarized here using whichever variation is
	 * open for pre-order and ships soonest (see PDW_Data::get_nearest_open_variation()).
	 * A variation-specific label/closed message is also shown once a shopper
	 * picks a variation on the product page, via filter_available_variation()
	 * in PDW_Purchasability and the enqueued button-text script below.
	 */
	public static function render_label() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		if ( $product instanceof WC_Product_Variable ) {
			$variation = PDW_Data::get_nearest_open_variation( $product );

			if ( $variation ) {
				echo '<p class="pdw-preorder-label">' . esc_html( PDW_Settings::get_open_label( $variation ) ) . '</p>';
			}

			return;
		}

		$state = PDW_Data::get_state( $product );

		if ( 'open' === $state ) {
			echo '<p class="pdw-preorder-label">' . esc_html( PDW_Settings::get_open_label( $product ) ) . '</p>';
		} elseif ( 'closed' === $state ) {
			echo '<p class="pdw-preorder-label pdw-preorder-closed">' . esc_html( PDW_Settings::get_closed_text() ) . '</p>';
		}
	}

	/**
	 * Enqueues the button-text swap script, only on a variable product's own
	 * page (it is a no-op everywhere else, including simple products, whose
	 * button text is already handled server-side by button_text() above).
	 */
	public static function enqueue_variation_script() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$product = wc_get_product( get_queried_object_id() );

		if ( ! $product instanceof WC_Product_Variable ) {
			return;
		}

		wp_enqueue_script(
			'pdw-variation',
			PDW_PLUGIN_URL . 'assets/js/variation.js',
			array( 'jquery', 'wc-add-to-cart-variation' ),
			PDW_VERSION,
			true
		);
	}

	/**
	 * Swaps "Add to cart" for the configured pre-order button text.
	 *
	 * @param string          $text    Default button text.
	 * @param WC_Product|null $product Product being rendered, when provided.
	 * @return string
	 */
	public static function button_text( $text, $product = null ) {
		if ( ! $product instanceof WC_Product ) {
			global $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- WooCommerce core also falls back to the loop global here.
		}

		if ( $product instanceof WC_Product && 'open' === PDW_Data::get_state( $product ) ) {
			return PDW_Settings::get_button_text();
		}

		return $text;
	}

	/**
	 * Adds the release date as a line under the item in the cart and checkout.
	 *
	 * @param array $item_data Existing cart item data lines.
	 * @param array $cart_item Cart item, with a 'data' key holding the WC_Product.
	 * @return array
	 */
	public static function add_cart_item_data( $item_data, $cart_item ) {
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;

		if ( $product instanceof WC_Product && 'open' === PDW_Data::get_state( $product ) ) {
			$release = PDW_Data::get_release( $product );
			if ( $release ) {
				$item_data[] = array(
					'key'   => __( 'Ships on', 'preorder-dates-for-woocommerce' ),
					'value' => PDW_Settings::format_date( $release ),
				);
			}
		}

		return $item_data;
	}
}
