<?php
/**
 * "Pre-order" tab panel for simple products.
 *
 * Expects $product (WC_Product), $enabled (bool), $cutoff and $release
 * (both "Y-m-d\TH:i" strings in store time) from PDFW_Product_Fields::render_panel().
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="pdfw_preorder_data" class="panel woocommerce_options_panel">
	<div class="options_group">
		<p class="form-field">
			<label for="pdfw_enabled"><?php esc_html_e( 'Enable pre-order', 'bitnary-preorder-dates-for-woocommerce' ); ?></label>
			<input type="checkbox" id="pdfw_enabled" name="pdfw_enabled" value="yes" <?php checked( $enabled ); ?> />
		</p>
		<p class="form-field pdfw-field-datetime">
			<label for="pdfw_cutoff"><?php esc_html_e( 'Orders close (cutoff)', 'bitnary-preorder-dates-for-woocommerce' ); ?></label>
			<input type="datetime-local" id="pdfw_cutoff" name="pdfw_cutoff" value="<?php echo esc_attr( $cutoff ); ?>" />
			<?php echo wc_help_tip( esc_html__( 'After this date and time, the product stops being purchasable. Store timezone.', 'bitnary-preorder-dates-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_help_tip() escapes internally. ?>
		</p>
		<p class="form-field pdfw-field-datetime">
			<label for="pdfw_release"><?php esc_html_e( 'Release / ships on', 'bitnary-preorder-dates-for-woocommerce' ); ?></label>
			<input type="datetime-local" id="pdfw_release" name="pdfw_release" value="<?php echo esc_attr( $release ); ?>" />
			<?php echo wc_help_tip( esc_html__( 'Once this date and time passes, pre-order is cleared automatically and the product behaves normally again. Store timezone.', 'bitnary-preorder-dates-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_help_tip() escapes internally. ?>
		</p>
		<?php
		/**
		 * Fires after the date fields in the product's Pre-order tab.
		 *
		 * @param WC_Product $product Product being edited.
		 */
		do_action( 'pdfw_product_panel_end', $product );
		?>
	</div>
</div>
