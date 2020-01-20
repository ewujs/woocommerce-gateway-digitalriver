jQuery( function( $ ) {
	'use strict';

	/**
	 * Object to handle Digital River admin functions.
	 */
	var wc_digitalriver_admin = {
		isTestMode: function() {
			return $( '#woocommerce_digitalriver_testmode' ).is( ':checked' );
		},

		getApiKey: function() {
			if ( wc_digitalriver_admin.isTestMode() ) {
				return $( '#woocommerce_digitalriver_test_api_key' ).val();
			} else {
				return $( '#woocommerce_digitalriver_api_key' ).val();
			}
		},

		/**
		 * Initialize.
		 */
		init: function() {
			$( document.body ).on( 'change', '#woocommerce_digitalriver_testmode', function() {
				var test_api_key = $( '#woocommerce_digitalriver_test_api_key' ).parents( 'tr' ).eq( 0 );
				var	live_api_key = $( '#woocommerce_digitalriver_api_key' ).parents( 'tr' ).eq( 0 );

				if ( $( this ).is( ':checked' ) ) {
					test_api_key.show();
					live_api_key.hide();
				} else {
					test_api_key.hide();
					live_api_key.show();
				}
			} );

			$( '#woocommerce_digitalriver_testmode' ).change();

			// Toggle Payment Request buttons settings.
			$( '#woocommerce_digitalriver_payment_request' ).change( function() {
				if ( $( this ).is( ':checked' ) ) {
					$( '#woocommerce_digitalriver_payment_request_button_theme, #woocommerce_digitalriver_payment_request_button_type, #woocommerce_digitalriver_payment_request_button_height' ).closest( 'tr' ).show();
				} else {
					$( '#woocommerce_digitalriver_payment_request_button_theme, #woocommerce_digitalriver_payment_request_button_type, #woocommerce_digitalriver_payment_request_button_height' ).closest( 'tr' ).hide();
				}
			} ).change();
		}
	};

	wc_digitalriver_admin.init();
} );
