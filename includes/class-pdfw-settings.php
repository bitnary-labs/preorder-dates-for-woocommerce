<?php
/**
 * WooCommerce Settings > Products > "Pre-order dates" section, and the
 * helpers front-end code uses to read those settings.
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the settings section using WooCommerce's own settings API
 * (woocommerce_get_sections_products / woocommerce_get_settings_products),
 * so saving, sanitizing and the on-screen layout are handled by WooCommerce
 * core, the same way any other Settings > Products section works.
 *
 * @see https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/includes/admin/settings/class-wc-settings-page.php
 */
class PDFW_Settings {

	const SECTION_ID     = 'preorder-dates';
	const OPTION_LABEL   = 'pdfw_label_format';
	const OPTION_BUTTON  = 'pdfw_button_text';
	const OPTION_CLOSED  = 'pdfw_closed_text';
	const OPTION_DATE_FT = 'pdfw_date_format';

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_get_sections_products', array( __CLASS__, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( __CLASS__, 'add_settings' ), 10, 2 );
	}

	/**
	 * Adds "Pre-order dates" to the list of Settings > Products sections.
	 *
	 * @param array $sections Existing sections, id => label.
	 * @return array
	 */
	public static function add_section( $sections ) {
		$sections[ self::SECTION_ID ] = __( 'Pre-order dates', 'bitnary-preorder-dates-for-woocommerce' );
		return $sections;
	}

	/**
	 * Returns the settings fields for the "Pre-order dates" section.
	 *
	 * @param array  $settings        Existing settings for the current section.
	 * @param string $current_section Section currently being displayed.
	 * @return array
	 */
	public static function add_settings( $settings, $current_section ) {
		if ( self::SECTION_ID !== $current_section ) {
			return $settings;
		}

		$fields = array(
			array(
				'title' => __( 'Pre-order dates', 'bitnary-preorder-dates-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'pdfw_text_options',
				'desc'  => __( 'Text shown on the product page, shop listings, the cart and checkout for products in pre-order.', 'bitnary-preorder-dates-for-woocommerce' ),
			),
			array(
				'title'    => __( 'Label', 'bitnary-preorder-dates-for-woocommerce' ),
				'desc'     => __( 'Shown on the product page and in shop listings while pre-order is open. Placeholders: {release_date}, {cutoff_date}.', 'bitnary-preorder-dates-for-woocommerce' ),
				'id'       => self::OPTION_LABEL,
				'type'     => 'text',
				'css'      => 'min-width: 420px;',
				'default'  => self::default_label(),
				'desc_tip' => false,
			),
			array(
				'title'   => __( 'Button text', 'bitnary-preorder-dates-for-woocommerce' ),
				'desc'    => __( 'Replaces "Add to cart" while pre-order is open.', 'bitnary-preorder-dates-for-woocommerce' ),
				'id'      => self::OPTION_BUTTON,
				'type'    => 'text',
				'default' => __( 'Pre-order', 'bitnary-preorder-dates-for-woocommerce' ),
			),
			array(
				'title'   => __( 'Closed message', 'bitnary-preorder-dates-for-woocommerce' ),
				'desc'    => __( 'Shown once the order cutoff has passed and the product can no longer be bought.', 'bitnary-preorder-dates-for-woocommerce' ),
				'id'      => self::OPTION_CLOSED,
				'type'    => 'text',
				'default' => __( 'Pre-orders closed', 'bitnary-preorder-dates-for-woocommerce' ),
			),
			array(
				'title'   => __( 'Date format', 'bitnary-preorder-dates-for-woocommerce' ),
				'desc'    => __( 'PHP date format used for {release_date} and {cutoff_date}. Leave as-is to match Settings > General > Date Format.', 'bitnary-preorder-dates-for-woocommerce' ),
				'id'      => self::OPTION_DATE_FT,
				'type'    => 'text',
				'default' => get_option( 'date_format', 'F j, Y' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'pdfw_text_options',
			),
			array(
				'title' => __( 'Pro', 'bitnary-preorder-dates-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'pdfw_pro_options',
				'desc'  => self::pro_description(),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'pdfw_pro_options',
			),
		);

		/**
		 * Filters the fields shown in the Pre-order dates settings section.
		 *
		 * The Pro add-on uses this to drop its own fields in and to remove the
		 * upgrade box, instead of re-checking the current section itself.
		 *
		 * @since 0.2.0
		 *
		 * @param array $fields WooCommerce settings field definitions.
		 */
		return apply_filters( 'pdfw_settings_fields', $fields );
	}

	/**
	 * Static description of what the paid version adds. Nothing here is a
	 * partial feature of the free plugin; it is only a list of what is not
	 * included, with a link to the product site.
	 *
	 * @return string
	 */
	private static function pro_description() {
		$items = array(
			__( 'Warn about or block carts that mix pre-order and in-stock items', 'bitnary-preorder-dates-for-woocommerce' ),
			__( 'A reminder email sent a few days before the release date', 'bitnary-preorder-dates-for-woocommerce' ),
			__( 'Bulk edit pre-order dates across many products at once', 'bitnary-preorder-dates-for-woocommerce' ),
			__( 'Priority support', 'bitnary-preorder-dates-for-woocommerce' ),
		);

		$html = '<p>' . esc_html__( 'The free plugin above is the whole feature set for pre-order dates. The paid version adds:', 'bitnary-preorder-dates-for-woocommerce' ) . '</p><ul style="list-style: disc; margin-left: 1.5em;">';
		foreach ( $items as $item ) {
			$html .= '<li>' . esc_html( $item ) . '</li>';
		}
		$html .= '</ul><p><a href="' . esc_url( 'https://preorder.bitnarydigital.com/?src=plugin-settings' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Learn more', 'bitnary-preorder-dates-for-woocommerce' ) . '</a></p>';

		return $html;
	}

	/**
	 * The label used when the store has not customized it.
	 *
	 * @return string
	 */
	private static function default_label() {
		return __( 'Pre-order. Ships on {release_date}. Orders close {cutoff_date}.', 'bitnary-preorder-dates-for-woocommerce' );
	}

	/**
	 * Builds the front-end label for a product currently open for pre-order.
	 *
	 * @param WC_Product $product Product or variation.
	 * @return string
	 */
	public static function get_open_label( WC_Product $product ) {
		$format  = get_option( self::OPTION_LABEL, self::default_label() );
		$release = PDFW_Data::get_release( $product );
		$cutoff  = PDFW_Data::get_cutoff( $product );

		return strtr(
			$format,
			array(
				'{release_date}' => $release ? self::format_date( $release ) : '',
				'{cutoff_date}'  => $cutoff ? self::format_date( $cutoff ) : '',
			)
		);
	}

	/**
	 * The add-to-cart button text for a product open for pre-order.
	 *
	 * @return string
	 */
	public static function get_button_text() {
		return get_option( self::OPTION_BUTTON, __( 'Pre-order', 'bitnary-preorder-dates-for-woocommerce' ) );
	}

	/**
	 * The message shown once the cutoff has passed.
	 *
	 * @return string
	 */
	public static function get_closed_text() {
		return get_option( self::OPTION_CLOSED, __( 'Pre-orders closed', 'bitnary-preorder-dates-for-woocommerce' ) );
	}

	/**
	 * Formats a GMT timestamp using the configured date format, in the store
	 * timezone (wp_date() applies wp_timezone() automatically).
	 *
	 * @param int $timestamp GMT timestamp.
	 * @return string
	 */
	public static function format_date( $timestamp ) {
		$format = get_option( self::OPTION_DATE_FT );
		if ( ! $format ) {
			$format = get_option( 'date_format', 'F j, Y' );
		}
		return wp_date( $format, (int) $timestamp );
	}
}
