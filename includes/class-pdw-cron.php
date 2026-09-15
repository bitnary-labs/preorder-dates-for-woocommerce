<?php
/**
 * Daily backstop that clears pre-order data for products whose release date
 * has passed, for products that are not read (and so never hit the lazy
 * check in PDW_Data::get_state()) before then.
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

class PDW_Cron {

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( PDW_Install::CRON_HOOK, array( __CLASS__, 'run' ) );
	}

	/**
	 * Clears expired pre-order data on both simple products and variations.
	 */
	public static function run() {
		self::cleanup_post_type( 'product' );
		self::cleanup_post_type( 'product_variation' );
	}

	/**
	 * @param string $post_type 'product' or 'product_variation'.
	 */
	private static function cleanup_post_type( $post_type ) {
		$ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded to enabled pre-orders past release, run once a day.
					'relation' => 'AND',
					array(
						'key'   => PDW_Data::META_ENABLED,
						'value' => 'yes',
					),
					array(
						'key'     => PDW_Data::META_RELEASE,
						'value'   => time(),
						'compare' => '<',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product ) {
				PDW_Data::clear( $product );
			}
		}
	}
}
