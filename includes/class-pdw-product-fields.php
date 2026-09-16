<?php
/**
 * "Pre-order" tab and fields on the simple product data panel.
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variable products configure pre-order per variation instead (see
 * PDW_Variation_Fields), so this tab only targets simple products, the same
 * pattern used by every free pre-order plugin checked in the gap analysis
 * (softtent, YITH, PRENA all mark their product-level tab show_if_simple).
 */
class PDW_Product_Fields {

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ) );
	}

	/**
	 * Adds the Pre-order tab to the product data panel.
	 *
	 * @param array $tabs Existing product data tabs.
	 * @return array
	 */
	public static function add_tab( $tabs ) {
		$tabs['pdw_preorder'] = array(
			'label'    => __( 'Pre-order', 'preorder-dates-for-woocommerce' ),
			'target'   => 'pdw_preorder_data',
			'class'    => array( 'show_if_simple' ),
			'priority' => 21,
		);
		return $tabs;
	}

	/**
	 * Renders the panel content for the "Pre-order" tab.
	 */
	public static function render_panel() {
		global $post;

		$product = $post ? wc_get_product( $post->ID ) : null;
		if ( ! $product ) {
			return;
		}

		$enabled = PDW_Data::is_enabled( $product );
		$cutoff  = PDW_Data::gmt_timestamp_to_local_input( PDW_Data::get_cutoff( $product ) );
		$release = PDW_Data::gmt_timestamp_to_local_input( PDW_Data::get_release( $product ) );

		include PDW_PLUGIN_DIR . 'includes/views/product-panel.php';
	}

	/**
	 * Saves the fields when the product is saved.
	 *
	 * WooCommerce's own meta box save routine already verifies the
	 * "woocommerce_meta_nonce" / "woocommerce_save_data" nonce before firing
	 * 'woocommerce_process_product_meta'; this check is kept here too so the
	 * handler is safe on its own if ever called from elsewhere.
	 *
	 * @param int $post_id Product post ID.
	 */
	public static function save( $post_id ) {
		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		$product = wc_get_product( $post_id );
		if ( ! $product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		$enabled     = ! empty( $_POST['pdw_enabled'] );
		$cutoff_raw  = isset( $_POST['pdw_cutoff'] ) ? sanitize_text_field( wp_unslash( $_POST['pdw_cutoff'] ) ) : '';
		$release_raw = isset( $_POST['pdw_release'] ) ? sanitize_text_field( wp_unslash( $_POST['pdw_release'] ) ) : '';

		PDW_Data::save_from_request( $product, $enabled, $cutoff_raw, $release_raw );
	}
}
