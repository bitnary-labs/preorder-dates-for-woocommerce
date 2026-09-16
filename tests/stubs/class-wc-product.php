<?php
/**
 * A stand-in for WC_Product covering only the meta API the plugin uses.
 *
 * It records whether save() was called, which is how the tests assert that
 * PDW_Data::get_state() really does clear expired pre-order data rather than
 * just reporting it as gone.
 *
 * @package PreorderDatesForWooCommerce
 */

declare(strict_types=1);

/**
 * Fake product.
 */
class WC_Product {

	/**
	 * Meta key => value.
	 *
	 * @var array<string, mixed>
	 */
	private array $meta;

	/**
	 * Product id.
	 *
	 * @var int
	 */
	private int $id;

	/**
	 * How many times save() was called.
	 *
	 * @var int
	 */
	public int $saves = 0;

	/**
	 * @param array<string, mixed> $meta Initial meta.
	 * @param int                  $id   Product id.
	 */
	public function __construct( array $meta = array(), int $id = 1 ) {
		$this->meta = $meta;
		$this->id   = $id;
	}

	/**
	 * @param string $key    Meta key.
	 * @param bool   $single Single value.
	 * @return mixed Empty string when unset, matching WooCommerce.
	 */
	public function get_meta( string $key, bool $single = true ) {
		return $this->meta[ $key ] ?? '';
	}

	/**
	 * @param string $key   Meta key.
	 * @param mixed  $value Value.
	 */
	public function update_meta_data( string $key, $value ): void {
		$this->meta[ $key ] = $value;
	}

	/**
	 * @param string $key Meta key.
	 */
	public function delete_meta_data( string $key ): void {
		unset( $this->meta[ $key ] );
	}

	/**
	 * Records the write.
	 */
	public function save(): void {
		++$this->saves;
	}

	/**
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Whether any pre-order meta is left, for assertions.
	 *
	 * @return array<string, mixed>
	 */
	public function all_meta(): array {
		return $this->meta;
	}
}

/**
 * Variations are a subclass in WooCommerce, and the plugin type-hints the
 * parent, so the tests need the name to exist.
 */
class WC_Product_Variation extends WC_Product {}
