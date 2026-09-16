<?php
/**
 * Reads, writes and interprets pre-order meta for a product or variation.
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * All dates are stored as GMT Unix timestamps so comparisons never depend on
 * server or PHP timezone settings; only the admin UI and the front-end labels
 * convert them to and from the store timezone (wp_timezone()).
 */
class PDW_Data {

	const META_ENABLED = '_pdw_enabled';
	const META_CUTOFF  = '_pdw_cutoff_gmt';
	const META_RELEASE = '_pdw_release_gmt';

	/**
	 * Whether pre-order is turned on for this product or variation, as stored
	 * (before any lazy expiry check).
	 *
	 * @param WC_Product $product Product or variation.
	 * @return bool
	 */
	public static function is_enabled( $product ) {
		return $product instanceof WC_Product && 'yes' === $product->get_meta( self::META_ENABLED, true );
	}

	/**
	 * Reads the cutoff date, the moment the store stops taking orders.
	 *
	 * @param WC_Product $product Product or variation.
	 * @return int GMT timestamp, or 0 when unset.
	 */
	public static function get_cutoff( $product ) {
		$value = $product instanceof WC_Product ? $product->get_meta( self::META_CUTOFF, true ) : '';
		return $value ? (int) $value : 0;
	}

	/**
	 * Reads the release date, the moment the product ships and pre-order ends.
	 *
	 * @param WC_Product $product Product or variation.
	 * @return int GMT timestamp, or 0 when unset.
	 */
	public static function get_release( $product ) {
		$value = $product instanceof WC_Product ? $product->get_meta( self::META_RELEASE, true ) : '';
		return $value ? (int) $value : 0;
	}

	/**
	 * Resolves the current pre-order state, lazily clearing expired data along
	 * the way (the "verificação preguiçosa na leitura" required alongside the
	 * daily cron backstop in PDW_Cron).
	 *
	 * @param WC_Product|null $product Product or variation.
	 * @return string|false 'open', 'closed', or false when pre-order does not apply.
	 */
	public static function get_state( $product ) {
		if ( ! $product instanceof WC_Product || ! self::is_enabled( $product ) ) {
			return false;
		}

		$release = self::get_release( $product );
		if ( $release && time() >= $release ) {
			self::clear( $product );
			return false;
		}

		$cutoff = self::get_cutoff( $product );
		if ( $cutoff && time() >= $cutoff ) {
			return 'closed';
		}

		return 'open';
	}

	/**
	 * Finds the variation that is open for pre-order and ships soonest, to
	 * summarize a variable product's pre-order status where only one line can
	 * be shown (shop loop, and the product page before a variation is picked).
	 * Falls back to the first open variation found when none has a release
	 * date set.
	 *
	 * @param WC_Product_Variable $product Variable product.
	 * @return WC_Product_Variation|null
	 */
	public static function get_nearest_open_variation( WC_Product_Variable $product ) {
		$fallback        = null;
		$nearest         = null;
		$nearest_release = 0;

		foreach ( $product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( ! $variation instanceof WC_Product_Variation || 'open' !== self::get_state( $variation ) ) {
				continue;
			}

			if ( ! $fallback ) {
				$fallback = $variation;
			}

			$release = self::get_release( $variation );

			if ( $release && ( ! $nearest || $release < $nearest_release ) ) {
				$nearest         = $variation;
				$nearest_release = $release;
			}
		}

		return $nearest ? $nearest : $fallback;
	}

	/**
	 * Removes all pre-order meta from a product or variation and persists it.
	 * Called once the release date has passed, either lazily (get_state) or
	 * from the daily cron event (PDW_Cron).
	 *
	 * @param WC_Product $product Product or variation.
	 */
	public static function clear( WC_Product $product ) {
		$product->delete_meta_data( self::META_ENABLED );
		$product->delete_meta_data( self::META_CUTOFF );
		$product->delete_meta_data( self::META_RELEASE );
		$product->save();
	}

	/**
	 * Saves pre-order fields coming from the product or variation edit screen.
	 *
	 * @param WC_Product $product     Product or variation, already loaded.
	 * @param bool       $enabled     Whether the "enable pre-order" checkbox was checked.
	 * @param string     $cutoff_raw  Raw datetime-local value ("Y-m-d\TH:i") in store time, or ''.
	 * @param string     $release_raw Raw datetime-local value ("Y-m-d\TH:i") in store time, or ''.
	 */
	public static function save_from_request( WC_Product $product, $enabled, $cutoff_raw, $release_raw ) {
		$product->update_meta_data( self::META_ENABLED, $enabled ? 'yes' : 'no' );

		if ( $enabled ) {
			$product->update_meta_data( self::META_CUTOFF, self::local_input_to_gmt_timestamp( $cutoff_raw ) );
			$product->update_meta_data( self::META_RELEASE, self::local_input_to_gmt_timestamp( $release_raw ) );
		}

		$product->save();
	}

	/**
	 * Converts a "Y-m-d\TH:i" datetime-local input value, interpreted in the
	 * store's timezone (wp_timezone()), into a GMT Unix timestamp.
	 *
	 * @param string $value Raw input value.
	 * @return int GMT timestamp, or 0 when empty/invalid.
	 */
	public static function local_input_to_gmt_timestamp( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( '' === $value ) {
			return 0;
		}

		try {
			$date = new DateTime( $value, wp_timezone() );
		} catch ( Exception $e ) {
			return 0;
		}

		return $date->getTimestamp();
	}

	/**
	 * Converts a GMT Unix timestamp back into a "Y-m-d\TH:i" value in the
	 * store's timezone, for redisplaying in a datetime-local input.
	 *
	 * @param int $timestamp GMT timestamp.
	 * @return string
	 */
	public static function gmt_timestamp_to_local_input( $timestamp ) {
		if ( ! $timestamp ) {
			return '';
		}

		$date = new DateTime( '@' . (int) $timestamp );
		$date->setTimezone( wp_timezone() );

		return $date->format( 'Y-m-d\TH:i' );
	}
}
