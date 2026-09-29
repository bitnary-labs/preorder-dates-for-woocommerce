<?php
/**
 * Tests for the label building and date formatting in PDFW_Settings.
 *
 * @package PreorderDatesForWooCommerce
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @covers PDFW_Settings
 */
final class PdfwSettingsTest extends TestCase {

	protected function setUp(): void {
		PDFW_Test_State::reset();
	}

	private function preorder( int $cutoff, int $release ): WC_Product {
		return new WC_Product(
			array(
				PDFW_Data::META_ENABLED => 'yes',
				PDFW_Data::META_CUTOFF  => $cutoff,
				PDFW_Data::META_RELEASE => $release,
			)
		);
	}

	private function timestamp( string $utc ): int {
		return ( new DateTime( $utc, new DateTimeZone( 'UTC' ) ) )->getTimestamp();
	}

	public function test_date_is_formatted_in_the_store_timezone(): void {
		PDFW_Test_State::$timezone            = 'America/Sao_Paulo';
		PDFW_Test_State::$options['pdfw_date_format'] = 'Y-m-d H:i';

		// 2026-09-29 00:30 UTC is still 2026-09-28 in Sao Paulo.
		$this->assertSame(
			'2026-09-28 21:30',
			PDFW_Settings::format_date( $this->timestamp( '2026-09-29T00:30:00' ) )
		);
	}

	public function test_date_format_falls_back_to_the_site_setting(): void {
		PDFW_Test_State::$options['date_format'] = 'd/m/Y';

		$this->assertSame(
			'29/09/2026',
			PDFW_Settings::format_date( $this->timestamp( '2026-09-29T12:00:00' ) )
		);
	}

	public function test_label_replaces_both_tokens(): void {
		PDFW_Test_State::$options['pdfw_date_format'] = 'M j';

		$label = PDFW_Settings::get_open_label(
			$this->preorder(
				$this->timestamp( '2026-09-22T12:00:00' ),
				$this->timestamp( '2026-09-29T12:00:00' )
			)
		);

		$this->assertStringContainsString( 'Sep 29', $label );
		$this->assertStringContainsString( 'Sep 22', $label );
		$this->assertStringNotContainsString( '{release_date}', $label );
		$this->assertStringNotContainsString( '{cutoff_date}', $label );
	}

	public function test_a_missing_date_leaves_no_token_behind(): void {
		$label = PDFW_Settings::get_open_label( $this->preorder( 0, 0 ) );

		$this->assertStringNotContainsString( '{release_date}', $label );
		$this->assertStringNotContainsString( '{cutoff_date}', $label );
	}

	public function test_a_custom_label_is_used_verbatim(): void {
		PDFW_Test_State::$options['pdfw_label_format'] = 'Chega em {release_date}';
		PDFW_Test_State::$options['pdfw_date_format']  = 'd/m';

		$this->assertSame(
			'Chega em 29/09',
			PDFW_Settings::get_open_label( $this->preorder( 0, $this->timestamp( '2026-09-29T12:00:00' ) ) )
		);
	}

	public function test_the_upgrade_box_promises_only_what_pro_ships(): void {
		$fields = PDFW_Settings::add_settings( array(), PDFW_Settings::SECTION_ID );

		$pro = '';
		foreach ( $fields as $field ) {
			if ( isset( $field['id'] ) && 'pdfw_pro_options' === $field['id'] && isset( $field['desc'] ) ) {
				$pro = $field['desc'];
			}
		}

		$this->assertNotSame( '', $pro, 'the settings section must carry the Pro box' );
		// Two claims were dropped on purpose because they cannot ship as worded.
		// See docs: splitting a cart is a separate product, and an email cannot
		// hold a live countdown.
		$this->assertStringNotContainsString( 'split', $pro );
		$this->assertStringNotContainsString( 'countdown', $pro );
	}

	public function test_settings_for_another_section_are_untouched(): void {
		$this->assertSame( array( 'existing' ), PDFW_Settings::add_settings( array( 'existing' ), 'shipping' ) );
	}
}
