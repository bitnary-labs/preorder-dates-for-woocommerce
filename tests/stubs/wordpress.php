<?php
/**
 * Minimal stand-ins for the WordPress functions the tested classes call.
 *
 * Each one behaves the way WordPress documents it, with the state a test needs
 * exposed through PDW_Test_State so a test can set the store timezone or an
 * option and then assert on the result.
 *
 * @package PreorderDatesForWooCommerce
 */

declare(strict_types=1);

// WordPress defines these; the plugin code uses them.
defined( 'MINUTE_IN_SECONDS' ) || define( 'MINUTE_IN_SECONDS', 60 );
defined( 'HOUR_IN_SECONDS' ) || define( 'HOUR_IN_SECONDS', 3600 );
defined( 'DAY_IN_SECONDS' ) || define( 'DAY_IN_SECONDS', 86400 );

/**
 * Holds the fake site state for the duration of one test.
 */
class PDW_Test_State {

	/**
	 * Option name => value.
	 *
	 * @var array<string, mixed>
	 */
	public static array $options = array();

	/**
	 * Store timezone string.
	 *
	 * @var string
	 */
	public static string $timezone = 'UTC';

	/**
	 * Resets everything between tests.
	 */
	public static function reset(): void {
		self::$options  = array();
		self::$timezone = 'UTC';
	}
}

if ( ! function_exists( 'wp_timezone' ) ) {
	/**
	 * @return DateTimeZone
	 */
	function wp_timezone(): DateTimeZone {
		return new DateTimeZone( PDW_Test_State::$timezone );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * @param string $name    Option name.
	 * @param mixed  $default Value when the option is unset.
	 * @return mixed
	 */
	function get_option( string $name, $default = false ) {
		return array_key_exists( $name, PDW_Test_State::$options ) ? PDW_Test_State::$options[ $name ] : $default;
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	/**
	 * Formats a GMT timestamp in the store timezone, like WordPress does.
	 *
	 * @param string $format    Date format.
	 * @param int    $timestamp GMT timestamp.
	 * @return string
	 */
	function wp_date( string $format, int $timestamp ): string {
		$date = new DateTime( '@' . $timestamp );
		$date->setTimezone( wp_timezone() );
		return $date->format( $format );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function esc_html__( string $text, string $domain = 'default' ): string {
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * @param string $url URL.
	 * @return string
	 */
	function esc_url( string $url ): string {
		return $url;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @param int      $args     Accepted args.
	 * @return bool
	 */
	function add_filter( string $hook, $callback, int $priority = 10, int $args = 1 ): bool {
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * @param string $hook  Hook name.
	 * @param mixed  $value Value.
	 * @return mixed
	 */
	function apply_filters( string $hook, $value ) {
		return $value;
	}
}
