/**
 * Swaps the add-to-cart button text to match the pre-order state of the
 * variation currently selected on a variable product's page, and restores
 * the original text once the selection is cleared.
 *
 * Only enqueued on variable products (see PDW_Frontend::enqueue_variation_script()).
 * Listens to the 'found_variation'/'reset_data' events fired by WooCommerce's
 * own wc-add-to-cart-variation.js, which does not change button text itself,
 * only CSS classes.
 */
( function ( $ ) {
	'use strict';

	$( '.variations_form' ).each( function () {
		var $form = $( this );
		var $button = $form.find( '.single_add_to_cart_button' );
		var originalText = $button.text();

		$form.on( 'found_variation', function ( event, variation ) {
			$button.text( variation && variation.pdw_button_text ? variation.pdw_button_text : originalText );
		} );

		$form.on( 'reset_data', function () {
			$button.text( originalText );
		} );
	} );
} )( jQuery );
