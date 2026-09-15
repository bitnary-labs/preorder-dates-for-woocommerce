<?php
/**
 * Front-end label, button text and cart/checkout line for pre-order products.
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

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
	 */
	public static function render_label() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
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
