<?php
defined( 'ABSPATH' ) or exit;

return apply_filters(
	'wc_digitalriver_settings',
	array(
		'enabled'                       => array(
			'title'       => __( 'Enable/Disable', 'woocommerce-gateway-digitalriver' ),
			'label'       => __( 'Enable Digital River', 'woocommerce-gateway-digitalriver' ),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no',
		),
		'title'                         => array(
			'title'       => __( 'Title', 'woocommerce-gateway-digitalriver' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-digitalriver' ),
			'default'     => __( 'Credit Card (Digital River)', 'woocommerce-gateway-digitalriver' ),
			'desc_tip'    => true,
		),
		'description'                   => array(
			'title'       => __( 'Description', 'woocommerce-gateway-digitalriver' ),
			'type'        => 'text',
			'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-digitalriver' ),
			'default'     => __( 'Pay with your Credit Card via Digital River.', 'woocommerce-gateway-digitalriver' ),
			'desc_tip'    => true,
		),
		'testmode'                      => array(
			'title'       => __( 'Test mode', 'woocommerce-gateway-digitalriver' ),
			'label'       => __( 'Enable Test Mode', 'woocommerce-gateway-digitalriver' ),
			'type'        => 'checkbox',
			'description' => __( 'Place the payment gateway in test mode using test API keys.', 'woocommerce-gateway-digitalriver' ),
			'default'     => 'yes',
			'desc_tip'    => true,
		),
		'test_api_key'          => array(
			'title'       => __( 'Test Payment Service API Key', 'woocommerce-gateway-digitalriver' ),
			'type'        => 'text',
			'description' => __( 'Required to process payments via DigitalRiver.js', 'woocommerce-gateway-digitalriver' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'api_key'               => array(
			'title'       => __( 'Live Payment Service API Key', 'woocommerce-gateway-digitalriver' ),
			'type'        => 'text',
			'description' => __( 'Required to process payments via DigitalRiver.js', 'woocommerce-gateway-digitalriver' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'statement_descriptor'          => array(
			'title'       => __( 'Statement Descriptor', 'woocommerce-gateway-digitalriver' ),
			'type'        => 'text',
			'description' => __( 'Statement descriptors are limited to 22 characters, cannot use the special characters >, <, ", \, \', *, and must not consist solely of numbers. This will appear on your customer\'s statement in capital letters.', 'woocommerce-gateway-digitalriver' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'instructions'                  => array(
			'title'       => __( 'Instructions', 'woocommerce-gateway-digitalriver' ),
			'type'        => 'textarea',
			'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce-gateway-digitalriver' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'payment_request'               => array(
			'title'       => __( 'Payment Request Buttons', 'woocommerce-gateway-digitalriver' ),
			'label'       => sprintf( __( 'Enable Payment Request Buttons. (Apple Pay/Google Pay Payment Request API) %1$sBy using Apple Pay, you agree to %2$s and %3$s\'s terms of service.', 'woocommerce-gateway-digitalriver' ), '<br />', '<a href="#" target="_blank">Digital River</a>', '<a href="https://developer.apple.com/apple-pay/acceptable-use-guidelines-for-websites/" target="_blank">Apple</a>' ),
			'type'        => 'checkbox',
			'description' => __( 'If enabled, users will be able to pay using Apple Pay or Payment Request API if supported by the browser.', 'woocommerce-gateway-digitalriver' ),
			'default'     => 'yes',
			'desc_tip'    => true,
		)
	)
);