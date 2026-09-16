<?php
/**
 * Makes pre-order products purchasable before the cutoff (regardless of
 * normal stock rules) and blocks them afterwards, everywhere WooCommerce
 * checks purchasability: classic templates, classic add-to-cart, and the
 * Store API used by the cart/checkout blocks.
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether a product on pre-order can be bought, on every path
 * WooCommerce asks: the product page, the classic cart, and the Store API that
 * the cart and checkout blocks use.
 */
class PDW_Purchasability {

	/**
	 * Registers hooks.
	 */
	public static function init() {
		// WC_Product::is_purchasable() applies 'woocommerce_is_purchasable'; for a
		// variation, its is_purchasable() calls parent::is_purchasable() first (so
		// this same filter fires with the variation as $product) and then applies
		// 'woocommerce_variation_is_purchasable' on top, so one callback on both
		// covers simple products and variations alike.
		// @see https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/includes/abstracts/abstract-wc-product.php
		// @see https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/includes/class-wc-product-variation.php
		add_filter( 'woocommerce_is_purchasable', array( __CLASS__, 'filter_purchasable' ), 20, 2 );
		add_filter( 'woocommerce_variation_is_purchasable', array( __CLASS__, 'filter_purchasable' ), 20, 2 );

		// Forces "in stock" while pre-order is open so the add-to-cart form
		// renders regardless of the product's real stock status/quantity.
		// @see https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/includes/abstracts/abstract-wc-product.php
		add_filter( 'woocommerce_product_is_in_stock', array( __CLASS__, 'filter_in_stock' ), 20, 2 );

		// Hides the default stock badge/text while pre-order is open so it does
		// not clash with our own "Pre-order..." label.
		add_filter( 'woocommerce_get_availability', array( __CLASS__, 'filter_availability' ), 20, 2 );

		// The variation form's per-variation JSON (read by wc-add-to-cart-variation.js
		// on 'found_variation'/'reset_data') is built by
		// WC_Product_Variable::get_available_variation(), independently from the
		// filters above: its 'availability_html' key comes from wc_get_stock_html(
		// $variation ), and the parent's own add-to-cart form always renders (it is
		// purchasable even when a variation is not), so the variation's real
		// stock/availability text would otherwise leak through once selected. This
		// filter replaces it with our own label/closed message and adds a custom
		// field with the button text for our own small enqueued script to apply.
		// @see https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/includes/class-wc-product-variable.php
		add_filter( 'woocommerce_available_variation', array( __CLASS__, 'filter_available_variation' ), 20, 3 );

		// Classic (non-block) add-to-cart validation.
		// Signature confirmed from WC_Form_Handler::add_to_cart_handler_variable()
		// and WC_AJAX::add_to_cart(): ( $passed, $product_id, $quantity, $variation_id, $variations ).
		// @see https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/includes/class-wc-form-handler.php
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate_add_to_cart' ), 10, 5 );

		// Store API (cart/checkout blocks) add-to-cart validation. is_purchasable()
		// is already checked by Automattic\WooCommerce\StoreApi\Utilities\CartController::validate_add_to_cart(),
		// which is enough to block the request; this hook only replaces the
		// generic WooCommerce message with our own "Pre-orders closed" text.
		// @see https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/src/StoreApi/Utilities/CartController.php
		if ( class_exists( '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) {
			add_action( 'woocommerce_store_api_validate_add_to_cart', array( __CLASS__, 'validate_store_api_add_to_cart' ), 10, 2 );
		}
	}

	/**
	 * Lets an open pre-order be bought, and blocks a closed one.
	 *
	 * @param bool       $purchasable Current purchasable state.
	 * @param WC_Product $product     Product or variation.
	 * @return bool
	 */
	public static function filter_purchasable( $purchasable, $product ) {
		$state = PDW_Data::get_state( $product );

		if ( 'open' === $state ) {
			return true;
		}

		if ( 'closed' === $state ) {
			return false;
		}

		return $purchasable;
	}

	/**
	 * Reports an open pre-order as in stock, whatever the stock settings say.
	 *
	 * @param bool       $in_stock Current in-stock state.
	 * @param WC_Product $product  Product or variation.
	 * @return bool
	 */
	public static function filter_in_stock( $in_stock, $product ) {
		if ( 'open' === PDW_Data::get_state( $product ) ) {
			return true;
		}
		return $in_stock;
	}

	/**
	 * Replaces the availability text with the pre-order label or closed message.
	 *
	 * @param array      $availability Array with 'availability' (text) and 'class' keys.
	 * @param WC_Product $product      Product or variation.
	 * @return array
	 */
	public static function filter_availability( $availability, $product ) {
		if ( 'open' === PDW_Data::get_state( $product ) ) {
			return array(
				'availability' => '',
				'class'        => '',
			);
		}
		return $availability;
	}

	/**
	 * Carries the label and button text for one variation to the front end.
	 *
	 * @param array                $data      Variation data sent to the front-end script.
	 * @param WC_Product_Variable  $product   Parent variable product (unused).
	 * @param WC_Product_Variation $variation Variation.
	 * @return array
	 */
	public static function filter_available_variation( $data, $product, $variation ) {
		$state = PDW_Data::get_state( $variation );

		if ( 'open' === $state ) {
			$data['availability_html'] = '<p class="pdw-preorder-label">' . esc_html( PDW_Settings::get_open_label( $variation ) ) . '</p>';
			$data['pdw_button_text']   = PDW_Settings::get_button_text();
		} elseif ( 'closed' === $state ) {
			$data['availability_html'] = '<p class="pdw-preorder-label pdw-preorder-closed">' . esc_html( PDW_Settings::get_closed_text() ) . '</p>';
		}

		return $data;
	}

	/**
	 * Stops a closed pre-order from being added to the classic cart.
	 *
	 * @param bool     $passed       Whether validation has passed so far.
	 * @param int      $product_id   Product ID being added.
	 * @param int      $quantity     Quantity requested.
	 * @param int|null $variation_id Variation ID, when applicable.
	 * @return bool
	 */
	public static function validate_add_to_cart( $passed, $product_id, $quantity, $variation_id = 0 ) {
		$product = wc_get_product( $variation_id ? $variation_id : $product_id );

		if ( $product && 'closed' === PDW_Data::get_state( $product ) ) {
			wc_add_notice( PDW_Settings::get_closed_text(), 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Stops a closed pre-order from being added through the Store API.
	 *
	 * @param WC_Product      $product Product or variation being added via the Store API.
	 * @param WP_REST_Request $request Store API request.
	 *
	 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException When pre-order is closed.
	 */
	public static function validate_store_api_add_to_cart( $product, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $request is part of the woocommerce_store_api_validate_add_to_cart signature.
		if ( 'closed' === PDW_Data::get_state( $product ) ) {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
				'preorder_dates_for_woocommerce_closed',
				esc_html( PDW_Settings::get_closed_text() ),
				400
			);
		}
	}
}
