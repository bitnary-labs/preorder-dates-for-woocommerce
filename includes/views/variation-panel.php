<?php
/**
 * Pre-order fields for one variation row.
 *
 * Expects $loop (int), $variation (WP_Post), $enabled (bool), $cutoff and
 * $release (both "Y-m-d\TH:i" strings in store time) from
 * PDFW_Variation_Fields::render_fields().
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="pdfw-variation-fields">
	<p class="form-row form-row-full">
		<label for="pdfw_variation_enabled_<?php echo esc_attr( $loop ); ?>">
			<input type="checkbox" class="checkbox" id="pdfw_variation_enabled_<?php echo esc_attr( $loop ); ?>" name="pdfw_variation_enabled[<?php echo esc_attr( $loop ); ?>]" value="yes" <?php checked( $enabled ); ?> />
			<?php esc_html_e( 'Enable pre-order for this variation', 'bitnary-preorder-dates-for-woocommerce' ); ?>
		</label>
	</p>
	<p class="form-row form-row-first">
		<label for="pdfw_variation_cutoff_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Orders close (cutoff)', 'bitnary-preorder-dates-for-woocommerce' ); ?></label>
		<input type="datetime-local" id="pdfw_variation_cutoff_<?php echo esc_attr( $loop ); ?>" name="pdfw_variation_cutoff[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( $cutoff ); ?>" />
	</p>
	<p class="form-row form-row-last">
		<label for="pdfw_variation_release_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Release / ships on', 'bitnary-preorder-dates-for-woocommerce' ); ?></label>
		<input type="datetime-local" id="pdfw_variation_release_<?php echo esc_attr( $loop ); ?>" name="pdfw_variation_release[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( $release ); ?>" />
	</p>
</div>
