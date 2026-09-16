<?php
/**
 * Pre-order fields on each variation panel, inside the "Variations" tab.
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Uses the same 'woocommerce_product_after_variable_attributes' /
 * 'woocommerce_save_product_variation' hook pair used by the free pre-order
 * plugins checked in the gap analysis (softtent's src/Tabs.php:24, YITH's
 * class-yith-pre-order-edit-product-page.php:114, PRENA's admin/product.php:19)
 * to inject a panel inside the existing Variations tab rather than a
 * separate one.
 */
class PDW_Variation_Fields {

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'render_fields' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * @param int     $loop           Position of the variation in the form.
	 * @param array   $variation_data Variation form data (unused; read from the product instead).
	 * @param WP_Post $variation      Variation post object.
	 */
	public static function render_fields( $loop, $variation_data, $variation ) {
		$product = wc_get_product( $variation->ID );
		if ( ! $product ) {
			return;
		}

		$enabled = PDW_Data::is_enabled( $product );
		$cutoff  = PDW_Data::gmt_timestamp_to_local_input( PDW_Data::get_cutoff( $product ) );
		$release = PDW_Data::gmt_timestamp_to_local_input( PDW_Data::get_release( $product ) );

		include PDW_PLUGIN_DIR . 'includes/views/variation-panel.php';
	}

	/**
	 * Saves the fields for one variation.
	 *
	 * The AJAX handler that calls this action (WC_AJAX::save_product_variations)
	 * already checks the "save-variations" nonce via check_ajax_referer() before
	 * processing any variation; verified again here for a handler that is safe
	 * on its own.
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $loop         Position of the variation in the form.
	 */
	public static function save( $variation_id, $loop ) {
		if ( empty( $_POST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'save-variations' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $variation_id ) ) {
			return;
		}

		$product = wc_get_product( $variation_id );
		if ( ! $product ) {
			return;
		}

		$field       = 'pdw_variation_enabled';
		$enabled     = ! empty( $_POST[ $field ][ $loop ] );
		$cutoff_raw  = isset( $_POST['pdw_variation_cutoff'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['pdw_variation_cutoff'][ $loop ] ) ) : '';
		$release_raw = isset( $_POST['pdw_variation_release'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['pdw_variation_release'][ $loop ] ) ) : '';

		PDW_Data::save_from_request( $product, $enabled, $cutoff_raw, $release_raw );
	}
}
