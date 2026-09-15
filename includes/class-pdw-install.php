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
class PDW_Install {

	/**
	 * Cron hook used to clear expired pre-order data once a day, as a backstop for
	 * products that are not read (and therefore not lazily cleaned up) before their
	 * release date.
	 */
	const CRON_HOOK = 'pdw_daily_cleanup';

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
}
