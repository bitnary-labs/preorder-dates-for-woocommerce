<?php
/**
 * Where the free plugin mentions the paid version.
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Three quiet pointers to the paid version: a link in the plugins list, a box
 * on the Pre-orders screen and a line in the product's Pre-order tab. None of
 * them is a dismissible or site-wide notice, and none gates anything the free
 * plugin does. All of them go away once the paid build is installed.
 */
class PDFW_Upsell {

	const SITE_URL = 'https://preorder.bitnarydigital.com/';

	/**
	 * Registers hooks, unless the paid code is already here.
	 */
	public static function init() {
		if ( self::is_paid_build() ) {
			return;
		}

		add_filter( 'plugin_action_links_' . plugin_basename( PDFW_PLUGIN_FILE ), array( __CLASS__, 'action_links' ) );
		add_action( 'pdfw_admin_page_after', array( __CLASS__, 'render_admin_box' ) );
		add_action( 'pdfw_product_panel_end', array( __CLASS__, 'render_panel_hint' ) );
	}

	/**
	 * Whether this copy of the plugin is the paid build.
	 *
	 * @return bool
	 */
	public static function is_paid_build() {
		return is_readable( PDFW_PLUGIN_DIR . 'pro/load.php' );
	}

	/**
	 * Product site URL, tagged with where the click came from.
	 *
	 * @param string $source Short identifier of the screen.
	 * @return string
	 */
	public static function url( $source ) {
		return add_query_arg( 'src', $source, self::SITE_URL );
	}

	/**
	 * Adds "Settings" and "Get Pro" next to Deactivate in the plugins list.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public static function action_links( $links ) {
		$settings = admin_url( 'admin.php?page=wc-settings&tab=products&section=' . PDFW_Settings::SECTION_ID );

		array_unshift(
			$links,
			'<a href="' . esc_url( $settings ) . '">' . esc_html__( 'Settings', 'bitnary-preorder-dates-for-woocommerce' ) . '</a>'
		);
		$links[] = '<a href="' . esc_url( self::url( 'plugins-list' ) ) . '" target="_blank" rel="noopener noreferrer" style="font-weight:600">' . esc_html__( 'Get Pro', 'bitnary-preorder-dates-for-woocommerce' ) . '</a>';

		return $links;
	}

	/**
	 * Box at the end of the Pre-orders screen. When pre-orders are open, it
	 * leads with the reminder email, which is what those customers would get.
	 *
	 * @param int $open_count Products and variations currently taking orders.
	 */
	public static function render_admin_box( $open_count = 0 ) {
		?>
		<div class="card" style="max-width:640px">
			<h2 class="title"><?php esc_html_e( 'Pre-order Pro', 'bitnary-preorder-dates-for-woocommerce' ); ?></h2>
			<?php if ( $open_count > 0 ) : ?>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of products taking pre-orders. */
							_n(
								'%d product is taking pre-orders. Pro emails those customers a few days before it ships.',
								'%d products are taking pre-orders. Pro emails those customers a few days before they ship.',
								$open_count,
								'bitnary-preorder-dates-for-woocommerce'
							),
							$open_count
						)
					);
					?>
				</p>
			<?php endif; ?>
			<ul style="list-style:disc;margin-left:1.5em">
				<li><?php esc_html_e( 'A reminder email before the release date', 'bitnary-preorder-dates-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Warn about or block carts that mix pre-order and in-stock items', 'bitnary-preorder-dates-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Move the dates of many products at once', 'bitnary-preorder-dates-for-woocommerce' ); ?></li>
			</ul>
			<p>
				<a class="button" href="<?php echo esc_url( self::url( 'preorders-screen' ) ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'See Pro plans', 'bitnary-preorder-dates-for-woocommerce' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * One line under the dates in the product's Pre-order tab.
	 */
	public static function render_panel_hint() {
		?>
		<p class="form-field description">
			<?php esc_html_e( 'Moving dates on many products?', 'bitnary-preorder-dates-for-woocommerce' ); ?>
			<a href="<?php echo esc_url( self::url( 'product-tab' ) ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Bulk edit is in Pro.', 'bitnary-preorder-dates-for-woocommerce' ); ?>
			</a>
		</p>
		<?php
	}
}
