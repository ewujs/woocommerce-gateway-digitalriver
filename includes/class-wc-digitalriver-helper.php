<?php
defined( 'ABSPATH' ) or exit;

/**
 * Provides static methods as helpers.
 *
 * @since 1.0.0
 */
class WC_DigitalRiver_Helper {
	const META_NAME_DIGITALRIVER_CURRENCY = '_digitalriver_currency';
	/**
	 * Gets the Digital River currency for order.
	 *
	 * @since 4.1.0
	 * @param object $order
	 * @return string $currency
	 */
	public static function get_digitalriver_currency( $order = null ) {
		if ( is_null( $order ) ) {
			return false;
		}

		$order_id = WC_DigitalRiver_Helper::is_wc_lt( '3.0' ) ? $order->id : $order->get_id();

		return WC_DigitalRiver_Helper::is_wc_lt( '3.0' ) ? get_post_meta( $order_id, self::META_NAME_DIGITALRIVER_CURRENCY, true ) : $order->get_meta( self::META_NAME_DIGITALRIVER_CURRENCY, true );
	}

	/**
	 * Updates the Digital River currency for order.
	 *
	 * @since 1.0.0
	 * @param object $order
	 * @param string $currency
	 */
	public static function update_digitalriver_currency( $order = null, $currency ) {
		if ( is_null( $order ) ) {
			return false;
		}

		$order_id = WC_DigitalRiver_Helper::is_wc_lt( '3.0' ) ? $order->id : $order->get_id();

		WC_DigitalRiver_Helper::is_wc_lt( '3.0' ) ? update_post_meta( $order_id, self::META_NAME_DIGITALRIVER_CURRENCY, $currency ) : $order->update_meta_data( self::META_NAME_DIGITALRIVER_CURRENCY, $currency );
	}

	/**
	 * Localize Stripe messages based on code
	 *
	 * @since 3.0.6
	 * @version 3.0.6
	 * @return array
	 */
	public static function get_localized_messages() {
		return apply_filters(
			'wc_digitalriver_localized_messages',
			array(
				'invalid_number'           => __( 'The card number is not a valid credit card number.', 'woocommerce-gateway-digitalriver' ),
				'invalid_expiry_month'     => __( 'The card\'s expiration month is invalid.', 'woocommerce-gateway-digitalriver' ),
				'invalid_expiry_year'      => __( 'The card\'s expiration year is invalid.', 'woocommerce-gateway-digitalriver' ),
				'invalid_cvc'              => __( 'The card\'s security code is invalid.', 'woocommerce-gateway-digitalriver' ),
				'incorrect_number'         => __( 'The card number is incorrect.', 'woocommerce-gateway-digitalriver' ),
				'incomplete_number'        => __( 'The card number is incomplete.', 'woocommerce-gateway-digitalriver' ),
				'incomplete_cvc'           => __( 'The card\'s security code is incomplete.', 'woocommerce-gateway-digitalriver' ),
				'incomplete_expiry'        => __( 'The card\'s expiration date is incomplete.', 'woocommerce-gateway-digitalriver' ),
				'expired_card'             => __( 'The card has expired.', 'woocommerce-gateway-digitalriver' ),
				'incorrect_cvc'            => __( 'The card\'s security code is incorrect.', 'woocommerce-gateway-digitalriver' ),
				'incorrect_zip'            => __( 'The card\'s zip code failed validation.', 'woocommerce-gateway-digitalriver' ),
				'invalid_expiry_year_past' => __( 'The card\'s expiration year is in the past', 'woocommerce-gateway-digitalriver' ),
				'card_declined'            => __( 'The card was declined.', 'woocommerce-gateway-digitalriver' ),
				'missing'                  => __( 'There is no card on a customer that is being charged.', 'woocommerce-gateway-digitalriver' ),
				'processing_error'         => __( 'An error occurred while processing the card.', 'woocommerce-gateway-digitalriver' ),
				'invalid_request_error'    => __( 'Unable to process this payment, please try again or use alternative method.', 'woocommerce-gateway-digitalriver' ),
				'invalid_sofort_country'   => __( 'The billing country is not accepted by SOFORT. Please try another country.', 'woocommerce-gateway-digitalriver' ),
				'email_invalid'            => __( 'Invalid email address, please correct and try again.', 'woocommerce-gateway-digitalriver' ),
			)
		);
	}

	/**
	 * Gets all the saved setting options from a specific method.
	 * If specific setting is passed, only return that.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $method The payment method to get the settings from.
	 * @param string $setting The name of the setting to get.
	 */
	public static function get_settings( $method = null, $setting = null ) {
		$all_settings = null === $method ? get_option( 'woocommerce_digitalriver_settings', array() ) : get_option( 'woocommerce_digitalriver_' . $method . '_settings', array() );

		if ( null === $setting ) {
			return $all_settings;
		}

		return isset( $all_settings[ $setting ] ) ? $all_settings[ $setting ] : '';
	}

	/**
	 * Checks if WC version is less than passed in version.
	 *
	 * @since 1.0.0
	 * @param string $version Version to check against.
	 * @return bool
	 */
	public static function is_wc_lt( $version ) {
		return version_compare( WC_VERSION, $version, '<' );
	}

	/**
	 * Gets the order by Digital River source ID.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $source_id
	 */
	public static function get_order_by_source_id( $source_id ) {
		return false;
	}

	/**
	 * Sanitize statement descriptor text.
	 *
	 * Stripe requires max of 22 characters and no
	 * special characters with ><"'.
	 *
	 * @since 1.0.0
	 * @param string $statement_descriptor
	 * @return string $statement_descriptor Sanitized statement descriptor
	 */
	public static function clean_statement_descriptor( $statement_descriptor = '' ) {
		$disallowed_characters = array( '<', '>', '"', "'" );

		// Remove special characters.
		$statement_descriptor = str_replace( $disallowed_characters, '', $statement_descriptor );

		$statement_descriptor = substr( trim( $statement_descriptor ), 0, 22 );

		return $statement_descriptor;
	}
}
