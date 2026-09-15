<?php
/**
 * Pre-order fields for one variation row.
 *
 * Expects $loop (int), $variation (WP_Post), $enabled (bool), $cutoff and
 * $release (both "Y-m-d\TH:i" strings in store time) from
 * PDW_Variation_Fields::render_fields().
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="pdw-variation-fields">
	<p class="form-row form-row-full">
		<label for="pdw_variation_enabled_<?php echo esc_attr( $loop ); ?>">
			<input type="checkbox" class="checkbox" id="pdw_variation_enabled_<?php echo esc_attr( $loop ); ?>" name="pdw_variation_enabled[<?php echo esc_attr( $loop ); ?>]" value="yes" <?php checked( $enabled ); ?> />
			<?php esc_html_e( 'Enable pre-order for this variation', 'preorder-dates-for-woocommerce' ); ?>
		</label>
	</p>
	<p class="form-row form-row-first">
		<label for="pdw_variation_cutoff_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Orders close (cutoff)', 'preorder-dates-for-woocommerce' ); ?></label>
		<input type="datetime-local" id="pdw_variation_cutoff_<?php echo esc_attr( $loop ); ?>" name="pdw_variation_cutoff[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( $cutoff ); ?>" />
	</p>
	<p class="form-row form-row-last">
		<label for="pdw_variation_release_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Release / ships on', 'preorder-dates-for-woocommerce' ); ?></label>
		<input type="datetime-local" id="pdw_variation_release_<?php echo esc_attr( $loop ); ?>" name="pdw_variation_release[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( $release ); ?>" />
	</p>
</div>
