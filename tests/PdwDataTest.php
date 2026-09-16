<?php
/**
 * Tests for PDW_Data: date conversion and the pre-order state machine.
 *
 * @package PreorderDatesForWooCommerce
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @covers PDW_Data
 */
final class PdwDataTest extends TestCase {

	protected function setUp(): void {
		PDW_Test_State::reset();
	}

	private function product( array $meta ): WC_Product {
		return new WC_Product( $meta );
	}

	private function preorder( int $cutoff, int $release ): WC_Product {
		return $this->product(
			array(
				PDW_Data::META_ENABLED => 'yes',
				PDW_Data::META_CUTOFF  => $cutoff,
				PDW_Data::META_RELEASE => $release,
			)
		);
	}

	public function test_disabled_product_has_no_state(): void {
		$product = $this->product( array( PDW_Data::META_ENABLED => 'no' ) );

		$this->assertFalse( PDW_Data::is_enabled( $product ) );
		$this->assertFalse( PDW_Data::get_state( $product ) );
	}

	public function test_product_without_meta_has_no_state(): void {
		$this->assertFalse( PDW_Data::get_state( $this->product( array() ) ) );
	}

	public function test_state_is_open_before_the_cutoff(): void {
		$product = $this->preorder( time() + 3600, time() + 86400 );

		$this->assertSame( 'open', PDW_Data::get_state( $product ) );
		$this->assertSame( 0, $product->saves, 'reading an open pre-order must not write' );
	}

	public function test_state_is_closed_after_the_cutoff_but_before_the_release(): void {
		$product = $this->preorder( time() - 60, time() + 86400 );

		$this->assertSame( 'closed', PDW_Data::get_state( $product ) );
		$this->assertSame( 0, $product->saves, 'a closed pre-order is still live data' );
	}

	public function test_the_cutoff_closes_the_product_on_the_exact_second(): void {
		$now     = time();
		$product = $this->preorder( $now, $now + 86400 );

		$this->assertSame( 'closed', PDW_Data::get_state( $product ) );
	}

	public function test_passing_the_release_date_clears_the_product(): void {
		$product = $this->preorder( time() - 86400, time() - 60 );

		$this->assertFalse( PDW_Data::get_state( $product ) );
		$this->assertSame( 1, $product->saves, 'expired pre-order data is cleared on read' );
		$this->assertSame( array(), $product->all_meta() );
	}

	public function test_release_is_checked_before_the_cutoff(): void {
		// Release already passed while the cutoff is still in the future, which
		// is a misconfiguration. Release wins, so the product goes back to normal
		// instead of being stuck sellable forever.
		$product = $this->preorder( time() + 86400, time() - 60 );

		$this->assertFalse( PDW_Data::get_state( $product ) );
		$this->assertSame( 1, $product->saves );
	}

	public function test_a_preorder_with_no_dates_stays_open(): void {
		$product = $this->preorder( 0, 0 );

		$this->assertSame( 'open', PDW_Data::get_state( $product ) );
	}

	public function test_local_input_is_read_in_the_store_timezone(): void {
		PDW_Test_State::$timezone = 'America/Sao_Paulo';

		// 2026-09-22 01:09 in Sao Paulo is 04:09 UTC.
		$this->assertSame(
			( new DateTime( '2026-09-22T04:09:00', new DateTimeZone( 'UTC' ) ) )->getTimestamp(),
			PDW_Data::local_input_to_gmt_timestamp( '2026-09-22T01:09' )
		);
	}

	public function test_timestamp_round_trips_through_the_store_timezone(): void {
		PDW_Test_State::$timezone = 'Europe/Lisbon';

		$input = '2026-09-29T09:00';
		$this->assertSame(
			$input,
			PDW_Data::gmt_timestamp_to_local_input( PDW_Data::local_input_to_gmt_timestamp( $input ) )
		);
	}

	public function test_round_trip_survives_a_daylight_saving_change(): void {
		// Lisbon leaves summer time on 2026-10-25. A date after the change must
		// still come back as the same wall clock time it went in as.
		PDW_Test_State::$timezone = 'Europe/Lisbon';

		$input = '2026-11-02T08:30';
		$this->assertSame(
			$input,
			PDW_Data::gmt_timestamp_to_local_input( PDW_Data::local_input_to_gmt_timestamp( $input ) )
		);
	}

	/**
	 * @dataProvider empty_inputs
	 */
	public function test_empty_or_broken_input_becomes_zero( $value ): void {
		$this->assertSame( 0, PDW_Data::local_input_to_gmt_timestamp( $value ) );
	}

	public static function empty_inputs(): array {
		return array(
			'empty string'    => array( '' ),
			'spaces'          => array( '   ' ),
			'null'            => array( null ),
			'array'           => array( array() ),
			'not a date'      => array( 'tomorrow-ish' ),
			'partial date'    => array( '2026-13-45T99:99' ),
		);
	}

	public function test_zero_timestamp_has_no_local_value(): void {
		$this->assertSame( '', PDW_Data::gmt_timestamp_to_local_input( 0 ) );
	}

	public function test_saving_stores_both_dates_as_gmt(): void {
		PDW_Test_State::$timezone = 'America/Sao_Paulo';
		$product                  = $this->product( array() );

		// Relative to now, so the test does not start failing once these dates pass.
		$tz      = new DateTimeZone( 'America/Sao_Paulo' );
		$cutoff  = ( new DateTime( '+7 days', $tz ) )->format( 'Y-m-d\TH:i' );
		$release = ( new DateTime( '+14 days', $tz ) )->format( 'Y-m-d\TH:i' );

		PDW_Data::save_from_request( $product, true, $cutoff, $release );

		$meta = $product->all_meta();
		$this->assertSame( 'yes', $meta[ PDW_Data::META_ENABLED ] );
		$this->assertSame( 'open', PDW_Data::get_state( $product ) );
		$this->assertLessThan( $meta[ PDW_Data::META_RELEASE ], $meta[ PDW_Data::META_CUTOFF ] );
		$this->assertSame( 1, $product->saves );
	}

	public function test_saving_disabled_leaves_the_old_dates_alone(): void {
		$product = $this->preorder( 111, 222 );

		PDW_Data::save_from_request( $product, false, '2026-09-22T01:09', '' );

		$meta = $product->all_meta();
		$this->assertSame( 'no', $meta[ PDW_Data::META_ENABLED ] );
		$this->assertSame( 111, $meta[ PDW_Data::META_CUTOFF ] );
		$this->assertSame( 222, $meta[ PDW_Data::META_RELEASE ] );
	}
}
