<?php
/**
 * "Pre-order" tab panel for simple products.
 *
 * Expects $product (WC_Product), $enabled (bool), $cutoff and $release
 * (both "Y-m-d\TH:i" strings in store time) from PDW_Product_Fields::render_panel().
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="pdw_preorder_data" class="panel woocommerce_options_panel">
	<div class="options_group">
		<p class="form-field">
			<label for="pdw_enabled"><?php esc_html_e( 'Enable pre-order', 'preorder-dates-for-woocommerce' ); ?></label>
			<input type="checkbox" id="pdw_enabled" name="pdw_enabled" value="yes" <?php checked( $enabled ); ?> />
		</p>
		<p class="form-field pdw-field-datetime">
			<label for="pdw_cutoff"><?php esc_html_e( 'Orders close (cutoff)', 'preorder-dates-for-woocommerce' ); ?></label>
			<input type="datetime-local" id="pdw_cutoff" name="pdw_cutoff" value="<?php echo esc_attr( $cutoff ); ?>" />
			<?php echo wc_help_tip( esc_html__( 'After this date and time, the product stops being purchasable. Store timezone.', 'preorder-dates-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_help_tip() escapes internally. ?>
		</p>
		<p class="form-field pdw-field-datetime">
			<label for="pdw_release"><?php esc_html_e( 'Release / ships on', 'preorder-dates-for-woocommerce' ); ?></label>
			<input type="datetime-local" id="pdw_release" name="pdw_release" value="<?php echo esc_attr( $release ); ?>" />
			<?php echo wc_help_tip( esc_html__( 'Once this date and time passes, pre-order is cleared automatically and the product behaves normally again. Store timezone.', 'preorder-dates-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_help_tip() escapes internally. ?>
		</p>
	</div>
</div>
