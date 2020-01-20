<?php
defined( 'ABSPATH' ) or exit;

/**
 * WC_Gateway_DigitalRiver class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_DigitalRiver extends WC_Payment_Gateway {
  /**
	 * Is test mode active?
	 *
	 * @var bool
	 */
  public $testmode;
  
  /**
   * Constructor for the gateway.
   */
  public function __construct() {
  
    $this->id                 = 'digitalriver';
    $this->has_fields         = true;
    $this->method_title       = __( 'Digital River', 'woocommerce_gateway_digitalriver' );
    $this->method_description = __( 'Digital River works by adding payment fields on the checkout and then sending the details to Digital River for verification.', 'woocommerce-gateway-digitalriver' );
    
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
    $this->init_settings();
    
    // Define user set variables
    $this->title        		= $this->get_option( 'title' );
    $this->description  		= $this->get_option( 'description' );
    $this->enabled      		= $this->get_option( 'enabled' );
    $this->testmode     		= $this->get_option( 'testmode' ) === 'yes';
    $this->api_key      		= $this->testmode ? $this->get_option( 'test_api_key' ) : $this->get_option( 'api_key' );
		$this->instructions			= $this->get_option( 'instructions', $this->description );
		$this->payment_request	= $this->get_option( 'payment_request', 'yes' ) === 'yes';
    
    // Actions
    add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
    
    // Customer Emails
    add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
  }

  /**
	 * Checks if keys are set.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function are_keys_set() {
		if ( empty( $this->api_key ) ) {
			return false;
		}

		return true;
	}
	
	/**
   * Initialize Gateway Settings Form Fields
   */
  public function init_form_fields() {
    $this->form_fields = require( dirname( __FILE__ ) . '/admin/digitalriver-settings.php' );
  }
  
  /**
	 * Payment form on checkout page
	 */
	public function payment_fields() {
		$user                 = wp_get_current_user();
		$total                = WC()->cart->total;
		$user_email           = '';
		$description          = $this->get_description();
		$description          = ! empty( $description ) ? $description : '';
		$firstname            = '';
		$lastname             = '';

		// If paying from order, we need to get total from order not cart.
		if ( isset( $_GET['pay_for_order'] ) && ! empty( $_GET['key'] ) ) { // wpcs: csrf ok.
			$order      = wc_get_order( wc_get_order_id_by_order_key( wc_clean( $_GET['key'] ) ) ); // wpcs: csrf ok, sanitization ok.
			$total      = $order->get_total();
			$user_email = WC_DigitalRiver_Helper::is_wc_lt( '3.0' ) ? $order->billing_email : $order->get_billing_email();
		} else {
			if ( $user->ID ) {
				$user_email = get_user_meta( $user->ID, 'billing_email', true );
				$user_email = $user_email ? $user_email : $user->user_email;
			}
		}

		if ( is_add_payment_method_page() ) {
			$firstname       = $user->user_firstname;
			$lastname        = $user->user_lastname;
		}

		ob_start();

		echo '<div
			id="digitalriver-payment-data"
			data-email="' . esc_attr( $user_email ) . '"
			data-full-name="' . esc_attr( $firstname . ' ' . $lastname ) . '"
			data-currency="' . esc_attr( strtolower( get_woocommerce_currency() ) ) . '"
		>';

		if ( $this->testmode ) {
			$description .= ' ' . sprintf( __( 'TEST MODE ENABLED. In test mode, you can use the card number 4444222233331111 with CVC 123 and a valid expiration date', 'woocommerce-gateway-digitalriver' ), '' );
		}

		$description = trim( $description );

		echo apply_filters( 'wc_digitalriver_description', wpautop( wp_kses_post( $description ) ), $this->id ); // wpcs: xss ok.

		$this->elements_form();

		do_action( 'wc_digitalriver_cards_payment_fields', $this->id );

		echo '</div>';

		ob_end_flush();
  }

  /**
	 * Renders the DR.js elements form.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function elements_form() {
		?>
		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>

      <div class="form-row form-row-wide">
        <label for="drjs-card-element"><?php esc_html_e( 'Card Number', 'woocommerce-gateway-digitalriver' ); ?> <span class="required">*</span></label>
        <div class="drjs-card-group">
          <div id="drjs-card-element" class="wc-drjs-elements-field">
          	<!-- a DR.js Element will be inserted here. -->
          </div>
          <i class="drjs-credit-card-brand drjs-card-brand" alt="Credit Card"></i>
        </div>
      </div>

      <div class="form-row form-row-first">
        <label for="drjs-exp-element"><?php esc_html_e( 'Expiry Date', 'woocommerce-gateway-digitalriver' ); ?> <span class="required">*</span></label>
        <div id="drjs-exp-element" class="wc-drjs-elements-field">
        	<!-- a DR.js Element will be inserted here. -->
        </div>
      </div>

      <div class="form-row form-row-last">
        <label for="drjs-cvc-element"><?php esc_html_e( 'Card Code (CVC)', 'woocommerce-gateway-digitalriver' ); ?> <span class="required">*</span></label>
				<div id="drjs-cvc-element" class="wc-drjs-elements-field">
					<!-- a DR.js Element will be inserted here. -->
				</div>
      </div>
      <div class="clear"></div>

			<!-- Used to display form errors -->
			<div class="drjs-source-errors" role="alert"></div>
			<br />
			<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
			<div class="clear"></div>
		</fieldset>
		<?php
	}
	
	/**
	 * Load admin scripts.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function admin_scripts() {
		if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '';

		wp_enqueue_script( 'woocommerce_digitalriver_admin', plugins_url( 'assets/js/digitalriver-admin' . $suffix . '.js', WC_DIGITALRIVER_MAIN_FILE ), array(), WC_DIGITALRIVER_VERSION, true );
	}

  /**
	 * Payment_scripts function.
	 *
	 * Outputs scripts used for Digital River payment
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function payment_scripts() {
		if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) { // wpcs: csrf ok.
			return;
		}

		// If Digital River is not enabled bail.
		if ( $this->enabled === 'no' ) {
			return;
		}

		// If keys are not set bail.
		if ( ! $this->are_keys_set() ) {
			return;
		}

		// If no SSL bail.
		if ( ! $this->testmode && ! is_ssl() ) {
			return;
		}

		//$current_theme = wp_get_theme();

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '';

		wp_register_style( 'digitalriver_styles', plugins_url( 'assets/css/digitalriver-styles.css', WC_DIGITALRIVER_MAIN_FILE ), array(), WC_DIGITALRIVER_VERSION );
		wp_enqueue_style( 'digitalriver_styles' );

    wp_enqueue_script( 'digitalriverJs', 'https://js.digitalriverws.com/v1/DigitalRiver.js' );
    wp_register_script( 'woocommerce_digitalriver', plugins_url( 'assets/js/digitalriver' . $suffix . '.js', WC_DIGITALRIVER_MAIN_FILE ), array( 'jquery-payment', 'digitalriverJs' ), WC_DIGITALRIVER_VERSION, true );

		$digitalriver_params = array(
			'key'                  => $this->api_key,
			'i18n_terms'           => __( 'Please accept the terms and conditions first', 'woocommerce-gateway-digitalriver' ),
			'i18n_required_fields' => __( 'Please fill in required checkout fields first', 'woocommerce-gateway-digitalriver' ),
		);

		// If we're on the pay page we need to pass digitalriver.js the address of the order.
		if ( isset( $_GET['pay_for_order'] ) && 'true' === $_GET['pay_for_order'] ) { // wpcs: csrf ok.
			$order_id = wc_get_order_id_by_order_key( urldecode( $_GET['key'] ) ); // wpcs: csrf ok, sanitization ok, xss ok.
			$order    = wc_get_order( $order_id );

			if ( is_a( $order, 'WC_Order' ) ) {
				$digitalriver_params['billing_first_name'] = WC_DigitalRiver_Helper::is_wc_lt( '3.0' ) ? $order->billing_first_name : $order->get_billing_first_name();
				$digitalriver_params['billing_last_name']  = WC_DigitalRiver_Helper::is_wc_lt( '3.0' ) ? $order->billing_last_name : $order->get_billing_last_name();
				$digitalriver_params['billing_address_1']  = WC_DigitalRiver_Helper::is_wc_lt( '3.0' ) ? $order->billing_address_1 : $order->get_billing_address_1();
				$digitalriver_params['billing_address_2']  = WC_DigitalRiver_Helper::is_wc_lt( '3.0' ) ? $order->billing_address_2 : $order->get_billing_address_2();
				$digitalriver_params['billing_state']      = WC_DigitalRiver_Helper::is_wc_lt( '3.0' ) ? $order->billing_state : $order->get_billing_state();
				$digitalriver_params['billing_city']       = WC_DigitalRiver_Helper::is_wc_lt( '3.0' ) ? $order->billing_city : $order->get_billing_city();
				$digitalriver_params['billing_postcode']   = WC_DigitalRiver_Helper::is_wc_lt( '3.0' ) ? $order->billing_postcode : $order->get_billing_postcode();
				$digitalriver_params['billing_country']    = WC_DigitalRiver_Helper::is_wc_lt( '3.0' ) ? $order->billing_country : $order->get_billing_country();
			}
		}

		$digitalriver_params['payment_intent_error']      = __( 'We couldn\'t initiate the payment. Please try again.', 'woocommerce-gateway-digitalriver' );
		$digitalriver_params['is_checkout']               = ( is_checkout() && empty( $_GET['pay_for_order'] ) ) ? 'yes' : 'no'; // wpcs: csrf ok.
		$digitalriver_params['ajaxurl']                   = WC_AJAX::get_endpoint( '%%endpoint%%' );
		$digitalriver_params['digitalriver_nonce']        = wp_create_nonce( '_wc_digitalriver_nonce' );
		$digitalriver_params['statement_descriptor']      = $this->statement_descriptor;
		$digitalriver_params['invalid_owner_name']        = __( 'Billing First Name and Last Name are required.', 'woocommerce-gateway-digitalriver' );
		$digitalriver_params['is_change_payment_page']    = isset( $_GET['change_payment_method'] ) ? 'yes' : 'no'; // wpcs: csrf ok.
		$digitalriver_params['is_add_payment_page']       = is_wc_endpoint_url( 'add-payment-method' ) ? 'yes' : 'no';
		$digitalriver_params['is_pay_for_order_page']     = is_wc_endpoint_url( 'order-pay' ) ? 'yes' : 'no';

		// Merge localized messages to be use in JS.
		$digitalriver_params = array_merge( $digitalriver_params, WC_DigitalRiver_Helper::get_localized_messages() );

		wp_localize_script( 'woocommerce_digitalriver', 'wc_digitalriver_params', apply_filters( 'wc_digitalriver_params', $digitalriver_params ) );

		$this->tokenization_script();
		wp_enqueue_script( 'woocommerce_digitalriver' );
  }

  /**
   * Output for the order received page.
   */
  public function thankyou_page() {
    if ( $this->instructions ) {
      echo wpautop( wptexturize( $this->instructions ) );
    }
  }


  /**
   * Add content to the WC emails.
   *
   * @access public
   * @param WC_Order $order
   * @param bool $sent_to_admin
   * @param bool $plain_text
   */
  public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
  
    if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
      echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
    }
  }


  /**
   * Process the payment and return the result
   *
   * @param int $order_id
   * @return array
   */
  public function process_payment( $order_id ) {

    $order = wc_get_order( $order_id );
    
    // Mark as on-hold (we're awaiting the payment)
    $order->update_status( 'on-hold', __( 'Awaiting Digital River payment', 'woocommerce-gateway-digitalriver' ) );
    
    // Reduce stock levels
    $order->reduce_order_stock();
    
    // Remove cart
    if ( isset( WC()->cart ) ) {
      WC()->cart->empty_cart();
    }

    // Return thank you page redirect
    return array(
      'result' 	=> 'success',
      'redirect'	=> $this->get_return_url( $order )
    );
  }
}