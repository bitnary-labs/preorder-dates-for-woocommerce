<?php
/**
 * Activation and deactivation routines.
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Schedules and clears the daily cleanup cron event.
 */
class PDFW_Install {

	/**
	 * Cron hook used to clear expired pre-order data once a day, as a backstop for
	 * products that are not read (and therefore not lazily cleaned up) before their
	 * release date.
	 */
	const CRON_HOOK = 'pdfw_daily_cleanup';

	/**
	 * Runs on plugin activation: schedules the daily cleanup event if it is not
	 * already scheduled.
	 */
	public static function activate() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Runs on plugin deactivation: clears the scheduled cleanup event. Product
	 * meta is left untouched so re-activating the plugin restores existing data.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Moves settings and product dates saved by 0.3.0 and earlier, which used
	 * the "pdw" prefix, over to the current "pdfw" keys. Runs once per site.
	 */
	public static function migrate_legacy_prefix() {
		if ( get_option( 'pdfw_legacy_prefix_migrated' ) ) {
			return;
		}

		foreach ( array( 'label_format', 'button_text', 'closed_text', 'date_format' ) as $name ) {
			$legacy = get_option( 'pdw_' . $name, null );
			if ( null !== $legacy ) {
				if ( false === get_option( 'pdfw_' . $name ) ) {
					update_option( 'pdfw_' . $name, $legacy );
				}
				delete_option( 'pdw_' . $name );
			}
		}

		$ids = get_posts(
			array(
				'post_type'      => array( 'product', 'product_variation' ),
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'meta_key'       => '_pdw_enabled', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-time migration.
			)
		);

		$legacy_keys = array(
			'_pdw_enabled'     => PDFW_Data::META_ENABLED,
			'_pdw_cutoff_gmt'  => PDFW_Data::META_CUTOFF,
			'_pdw_release_gmt' => PDFW_Data::META_RELEASE,
		);

		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product ) {
				continue;
			}
			foreach ( $legacy_keys as $old => $new ) {
				if ( $product->meta_exists( $old ) ) {
					$product->update_meta_data( $new, $product->get_meta( $old ) );
					$product->delete_meta_data( $old );
				}
			}
			$product->save();
		}

		wp_clear_scheduled_hook( 'pdw_daily_cleanup' );
		self::activate();

		update_option( 'pdfw_legacy_prefix_migrated', PDFW_VERSION, false );
	}
}
