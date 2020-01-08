<?php
/*
 * Plugin Name: WooCommerce Digital River Gateway
 * Plugin URI:  https://wordpress.org/plugins/woocommerce-gateway-digitalriver/
 * Description: Take credit card payments on your store using Digital River.
 * Author: Digital River
 * Author URI: https://digitalriver.com/
 * Version: 1.0.0
 *
 */

defined( 'ABSPATH' ) or exit;

function woocommerce_digitalriver_missing_wc_notice() {
	/* translators: 1. URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Digital River requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-gateway-digitalriver' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

add_action( 'plugins_loaded', 'woocommerce_gateway_digitalriver_init' );

function woocommerce_gateway_digitalriver_init() {
	// Make sure WooCommerce is active
	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		add_action( 'admin_notices', 'woocommerce_digitalriver_missing_wc_notice' );
		return;
	}

	if ( ! class_exists( 'WC_DigitalRiver' ) ) :
		define( 'WC_DIGITALRIVER_VERSION', '1.0.0' );
		define( 'WC_DIGITALRIVER_MAIN_FILE', __FILE__ );

		class WC_DigitalRiver {

			/**
			 * @var Singleton The reference the *Singleton* instance of this class
			 */
			private static $instance;

			/**
			 * Returns the *Singleton* instance of this class.
			 *
			 * @return Singleton The *Singleton* instance.
			 */
			public static function get_instance() {
				if ( null === self::$instance ) {
					self::$instance = new self();
				}
				return self::$instance;
			}

			/**
			 * Private clone method to prevent cloning of the instance of the
			 * *Singleton* instance.
			 *
			 * @return void
			 */
			private function __clone() {}

			/**
			 * Private unserialize method to prevent unserializing of the *Singleton*
			 * instance.
			 *
			 * @return void
			 */
			private function __wakeup() {}

			/**
			 * Protected constructor to prevent creating a new instance of the
			 * *Singleton* via the `new` operator from outside of this class.
			 */
			private function __construct() {
				add_action( 'admin_init', array( $this, 'install' ) );
				$this->init();
			}

			public function init() {
				require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-digitalriver.php';
				require_once dirname( __FILE__ ) . '/includes/class-wc-digitalriver-helper.php';

				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_digitalriver_gateways' ) );
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

				if ( version_compare( WC_VERSION, '3.4', '<' ) ) {
					add_filter( 'woocommerce_get_sections_checkout', array( $this, 'filter_gateway_order_admin' ) );
				}
			}

			/**
			 * Add the gateway to WC Available Gateways
			 * 
			 * @since 1.0.0
			 * @param array $gateways all available WC gateways
			 * @return array $gateways all WC gateways + Digital River gateway
			 */
			public function add_digitalriver_gateways( $gateways ) {
				$gateways[] = 'WC_Gateway_DigitalRiver';
				return $gateways;
			}

			/**
			 * Adds plugin page links
			 * 
			 * @since 1.0.0
			 * @param array $links all plugin links
			 * @return array $links all plugin links + Digital River links
			 */
			public function plugin_action_links( $links ) {
				$plugin_links = array(
					'<a href="admin.php?page=wc-settings&tab=checkout&section=digitalriver">' . esc_html__( 'Settings', 'woocommerce-gateway-digitalriver' ) . '</a>',
					'<a href="https://docs.woocommerce.com/document/digitalriver/">' . esc_html__( 'Docs', 'woocommerce-gateway-digitalriver' ) . '</a>'
				);
				return array_merge( $plugin_links, $links );
			}

			/**
			 * Updates the plugin version in db
			 *
			 * @since 1.0.0
			 * @version 1.0.0
			 */
			public function update_plugin_version() {
				delete_option( 'wc_digitalriver_version' );
				update_option( 'wc_digitalriver_version', WC_DIGITALRIVER_VERSION );
			}

			/**
			 * Handles upgrade routines.
			 *
			 * @since 1.0.0
			 * @version 1.0.0
			 */
			public function install() {
				if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) {
					return;
				}

				if ( ! defined( 'IFRAME_REQUEST' ) && ( WC_DIGITALRIVER_VERSION !== get_option( 'wc_digitalriver_version' ) ) ) {
					do_action( 'woocommerce_digitalriver_updated' );

					if ( ! defined( 'WC_DIGITALRIVER_INSTALLING' ) ) {
						define( 'WC_DIGITALRIVER_INSTALLING', true );
					}

					$this->update_plugin_version();
				}
			}

			/**
			 * Modifies the order of the gateways displayed in admin.
			 *
			 * @since 4.0.0
			 * @version 4.0.0
			 */
			public function filter_gateway_order_admin( $sections ) {
				unset( $sections['digitalriver'] );

				$sections['digitalriver'] = 'Digital River';

				return $sections;
			}
		}

		WC_DigitalRiver::get_instance();
	endif;
}