<?php
/**
 * The "Pre-orders" screen under the WooCommerce menu.
 *
 * Settings themselves stay in WooCommerce > Settings > Products > Pre-order
 * dates, where WooCommerce's own settings API handles saving and sanitizing.
 * This screen answers the question that page cannot: which products are on
 * pre-order right now, and what happens next.
 *
 * @package PreorderDatesForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the overview screen.
 */
class PDW_Admin_Page {

	// Defined in the main plugin file, because Freemius needs it before this
	// class is loaded in order to hang its Account and Upgrade screens here.
	const MENU_SLUG = PDW_MENU_SLUG;

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 60 );
	}

	/**
	 * Adds the screen as a WooCommerce submenu.
	 */
	public static function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Pre-orders', 'preorder-dates-for-woocommerce' ),
			__( 'Pre-orders', 'preorder-dates-for-woocommerce' ),
			'manage_woocommerce',
			self::MENU_SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Collects every product and variation that currently has pre-order enabled,
	 * split by state and ordered by whichever date comes next.
	 *
	 * Reads the meta directly rather than going through PDW_Data::get_state(),
	 * because that method clears expired rows as a side effect and an admin
	 * listing should not mutate data while it renders.
	 *
	 * @return array{open: array, closed: array}
	 */
	public static function collect() {
		global $wpdb;

		$cache_key = 'pdw_enabled_ids';
		$ids       = wp_cache_get( $cache_key, 'preorder-dates' );

		if ( false === $ids ) {
			// A meta_key lookup against an indexed column, run once per page load on
			// one admin screen. wc_get_products() cannot do this in a single call
			// because pre-order lives on variations as well as on products.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 500",
					PDW_Data::META_ENABLED,
					'yes'
				)
			);

			wp_cache_set( $cache_key, $ids, 'preorder-dates', MINUTE_IN_SECONDS );
		}

		$now    = time();
		$open   = array();
		$closed = array();

		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$cutoff  = PDW_Data::get_cutoff( $product );
			$release = PDW_Data::get_release( $product );

			if ( $release && $now >= $release ) {
				continue;
			}

			$row = array(
				'product' => $product,
				'cutoff'  => $cutoff,
				'release' => $release,
			);

			if ( $cutoff && $now >= $cutoff ) {
				$closed[] = $row;
			} else {
				$open[] = $row;
			}
		}

		$by_next = static function ( $a, $b ) {
			$left  = $a['cutoff'] ? $a['cutoff'] : $a['release'];
			$right = $b['cutoff'] ? $b['cutoff'] : $b['release'];
			return $left <=> $right;
		};

		usort( $open, $by_next );
		usort( $closed, $by_next );

		return array(
			'open'   => $open,
			'closed' => $closed,
		);
	}

	/**
	 * Renders the screen.
	 */
	public static function render() {
		$rows     = self::collect();
		$settings = admin_url( 'admin.php?page=wc-settings&tab=products&section=' . PDW_Settings::SECTION_ID );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Pre-orders', 'preorder-dates-for-woocommerce' ); ?></h1>

			<?php if ( ! $rows['open'] && ! $rows['closed'] ) : ?>
				<p>
					<?php esc_html_e( 'No product is on pre-order right now. Open a product, go to the Pre-order tab, and set the two dates.', 'preorder-dates-for-woocommerce' ); ?>
				</p>
			<?php endif; ?>

			<?php
			self::render_table(
				__( 'Taking orders', 'preorder-dates-for-woocommerce' ),
				__( 'Orders close', 'preorder-dates-for-woocommerce' ),
				$rows['open']
			);

			self::render_table(
				__( 'Closed, waiting for release', 'preorder-dates-for-woocommerce' ),
				__( 'Closed since', 'preorder-dates-for-woocommerce' ),
				$rows['closed']
			);
			?>

			<p>
				<a href="<?php echo esc_url( $settings ); ?>">
					<?php esc_html_e( 'Pre-order settings', 'preorder-dates-for-woocommerce' ); ?>
				</a>
			</p>

			<?php
			/**
			 * Fires at the end of the Pre-orders screen.
			 *
			 * The Pro add-on renders its own box here. Nothing in the free
			 * plugin hooks this.
			 */
			do_action( 'pdw_admin_page_after' );
			?>
		</div>
		<?php
	}

	/**
	 * Renders one of the two tables, or nothing when the group is empty.
	 *
	 * @param string $title       Table heading.
	 * @param string $date_column Label for the cutoff column.
	 * @param array  $rows        Rows from collect().
	 */
	private static function render_table( $title, $date_column, array $rows ) {
		if ( ! $rows ) {
			return;
		}
		?>
		<h2><?php echo esc_html( $title ); ?></h2>
		<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Product', 'preorder-dates-for-woocommerce' ); ?></th>
					<th><?php echo esc_html( $date_column ); ?></th>
					<th><?php esc_html_e( 'Ships on', 'preorder-dates-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $rows as $row ) : ?>
				<tr>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $row['product']->get_id() ) ); ?>">
							<?php echo esc_html( $row['product']->get_name() ); ?>
						</a>
					</td>
					<td><?php echo esc_html( $row['cutoff'] ? PDW_Settings::format_date( $row['cutoff'] ) : __( 'Not set', 'preorder-dates-for-woocommerce' ) ); ?></td>
					<td><?php echo esc_html( $row['release'] ? PDW_Settings::format_date( $row['release'] ) : __( 'Not set', 'preorder-dates-for-woocommerce' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}
