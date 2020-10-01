<?php
/*
 * Plugin Name: WorldPay Hosted Payment Gateway
 * Plugin URI: #
 * Description: WooCommerce custom payment gateway integration on cloudways.
 * Version: 1.0.18
 * Author: priyankkpatel
 * Author URI: https://profiles.wordpress.org/priyankkpatel
 * Text Domain: woo-worldpay-hosted-payment-gateway
 */

add_filter( 'woocommerce_payment_gateways', 'whpg_custom_worldpay_class' );
function whpg_custom_worldpay_class( $methods ) {
	$methods[] = 'WC_worldpay_gateway_class';
	return $methods;
}
// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'whpg_custom_worldpay_action_links' );
function whpg_custom_worldpay_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'woo-worldpay-hosted-payment-gateway' ) . '</a>',
	);
	return array_merge( $plugin_links, $links );
}

add_action( 'wp_enqueue_scripts', 'whpg_load_worldpat_scripts' );
function whpg_load_worldpat_scripts(){
	if( is_checkout() ){
		wp_enqueue_script( 'worldpay-script', 'https://payments.worldpay.com/resources/hpp/integrations/embedded/js/hpp-embedded-integration-library.js', array( 'jquery' ) );
		wp_enqueue_style( 'worldpay-style', 'https://payments.worldpay.com/resources/hpp/integrations/embedded/css/hpp-embedded-integration-library.css' );
		wp_enqueue_style( 'worldpay-custom-style', get_bloginfo('url') . '/wp-content/plugins/worldpay/worldpay-style.css' );
	}
}


add_action('wp_footer', 'whpg_load_payment_iframe');
function whpg_load_payment_iframe(){
	if( is_checkout() && isset( $_COOKIE['worldpay_url'] ) ){
		$order_id = $_GET['order'];
		$order = new WC_Order($order_id);
		?>
		<div id='custom-html' class="custom-worldpay-html"></div>
    
	    <script type="text/javascript">
	 
	    //your options
	    var customOptions = {
	      iframeIntegrationId: 'libraryObject',
	      iframeHelperURL: '<?php echo get_bloginfo('url') ?>/wp-content/plugins/worldpay/helper.html',
	      iframeBaseURL: '<?php echo get_bloginfo('url') ?>',
	      url: '<?php echo $_COOKIE['worldpay_url'].'&successURL='.$_COOKIE['worldpay_success_url'].'&cancelURL='.$_COOKIE['worldpay_cancel_url'].'&failureURL='.get_bloginfo('url').'&pendingURL='.get_bloginfo('url').'&errorURL='.get_bloginfo('url') ?>',
	      type: 'iframe',
	      target: 'custom-html',
	      accessibility: true,
	      debug: false,
	      language: 'en',
	      country: 'gb',
	      preferredPaymentMethod: '',
	    };
	    //initialise the library and pass options
	    var libraryObject = new WPCL.Library();
	    libraryObject.setup(customOptions);
	    </script>
		<?php 
	    setcookie("worldpay_url", "", time() + 3600, '/');
    	setcookie("worldpay_success_url", "", time() + 3600, '/');
    	setcookie("worldpay_cancel_url", "", time() + 3600, '/');
    }
}


add_action( 'plugins_loaded', 'whpg_custom_worldpay_init', 0 );
function whpg_custom_worldpay_init() {
	class WC_worldpay_gateway_class extends WC_Payment_Gateway {

		function __construct() {

			$this->id = "custom-worldpay";	// global ID
			$this->method_title = __( "Custom Worldpay", 'woo-worldpay-hosted-payment-gateway' );	// Show Title
			$this->method_description = __( "Custom Worldpay Payment Gateway Plug-in for WooCommerce", 'woo-worldpay-hosted-payment-gateway' );	// Show Description
			$this->title = __( "custom Worldpay", 'woo-worldpay-hosted-payment-gateway' );	// vertical tab title
			$this->icon = null;
			$this->has_fields = true;
			//$this->supports = array( 'default_credit_card_form' );	// support default form with credit card
			$this->init_form_fields();	// setting defines
			$this->init_settings();	// load the setting

			// Turn these settings into variables we can use
			foreach ( $this->settings as $setting_key => $value ) {
				$this->$setting_key = $value;
			}
			
			// further check of SSL if you want
			add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
			
			// Save settings
			if ( is_admin() ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			}		
		}

		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'		=> __( 'Enable / Disable', 'woo-worldpay-hosted-payment-gateway' ),
					'label'		=> __( 'Enable this payment gateway', 'woo-worldpay-hosted-payment-gateway' ),
					'type'		=> 'checkbox',
					'default'	=> 'no',
				),
				'title' => array(
					'title'		=> __( 'Title', 'woo-worldpay-hosted-payment-gateway' ),
					'type'		=> 'text',
					'desc_tip'	=> __( 'Payment title of checkout process.', 'woo-worldpay-hosted-payment-gateway' ),
					'default'	=> __( 'Credit card', 'woo-worldpay-hosted-payment-gateway' ),
				),
				'description' => array(
					'title'		=> __( 'Description', 'woo-worldpay-hosted-payment-gateway' ),
					'type'		=> 'textarea',
					'desc_tip'	=> __( 'Payment description of checkout process.', 'woo-worldpay-hosted-payment-gateway' ),
					'default'	=> __( 'Successfully payment through credit card.', 'woo-worldpay-hosted-payment-gateway' ),
					'css'		=> 'max-width:450px;'
				),
				'merchant_code' => array(
					'title'		=> __( 'Merchant Code', 'woo-worldpay-hosted-payment-gateway' ),
					'type'		=> 'text',
					'desc_tip'	=> __( 'Please enter Merchant Code provided by WorldPay here.', 'woo-worldpay-hosted-payment-gateway' ),
				),
				'xml_username' => array(
					'title'		=> __( 'XML Username', 'woo-worldpay-hosted-payment-gateway' ),
					'type'		=> 'text',
					'desc_tip'	=> __( 'Please enter XML Username provided by WorldPay here.', 'woo-worldpay-hosted-payment-gateway' ),
				),
				'xml_password' => array(
					'title'		=> __( 'XML Password', 'woo-worldpay-hosted-payment-gateway' ),
					'type'		=> 'text',
					'desc_tip'	=> __( 'Please enter XML Password provided by WorldPay here.', 'woo-worldpay-hosted-payment-gateway' ),
				),
				'installation_id' => array(
					'title'		=> __( 'Installation id', 'woo-worldpay-hosted-payment-gateway' ),
					'type'		=> 'text',
					'desc_tip'	=> __( 'Please enter installation id provided by WorldPay here.', 'woo-worldpay-hosted-payment-gateway' ),
				),
				'environment' => array(
					'title'		=> __( 'Worldpay Test Mode', 'woo-worldpay-hosted-payment-gateway' ),
					'label'		=> __( 'Enable Test Mode', 'woo-worldpay-hosted-payment-gateway' ),
					'type'		=> 'checkbox',
					'description' => __( 'This is the test mode of gateway.', 'woo-worldpay-hosted-payment-gateway' ),
					'default'	=> 'no',
				)
			);		
		}
		
		// Response handled for payment gateway
		public function process_payment( $order_id ) {
			global $woocommerce;

			$customer_order = new WC_Order( $order_id );
			$world_options =  get_option( 'woocommerce_custom-worldpay_settings' );
			$environment = ( $world_options['environment'] == "yes" ) ? TRUE : FALSE;	// check whether test mode or not
			$merchant_code = $world_options['merchant_code'];
			$installation_id = $world_options['installation_id'];
			$username = $world_options['xml_username'];
			$password = htmlspecialchars_decode($world_options['xml_password']);
			$currency_code = get_woocommerce_currency();
			$order_number = $customer_order->get_order_number();
			$cart_total = $customer_order->order_total*100;

			// Decide which URL to post to
			$environment_url = ( FALSE == $environment ) ? 'https://secure.worldpay.com/jsp/merchant/xml/paymentService.jsp' : 'https://secure-test.worldpay.com/jsp/merchant/xml/paymentService.jsp';

			$worldpay_xml_data = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC "-//Worldpay//DTD Worldpay PaymentService v1//EN"   "http://dtd.worldpay.com/paymentService_v1.dtd"><paymentService version="1.4" merchantCode="'.$merchant_code.'"><submit><order orderCode="'.$order_number.'" installationId="'.$installation_id.'"><description>'.$order_id.'</description><amount currencyCode="'.$currency_code.'" exponent="2" value="'.$cart_total.'"/><paymentMethodMask><include code="ALL"/></paymentMethodMask><shopper><shopperEmailAddress>'.$customer_order->billing_email.'</shopperEmailAddress></shopper><shippingAddress><address><address1>'.$customer_order->shipping_address_1.'</address1> <postalCode>'.$customer_order->shipping_postcode.'</postalCode><city>'.$customer_order->shipping_city.'</city><state>'.$customer_order->shipping_state.'</state><countryCode>'.$customer_order->shipping_country.'</countryCode></address></shippingAddress><billingAddress><address><address1>'.$customer_order->billing_address_1.'</address1><postalCode>'.$customer_order->billing_postcode.'</postalCode><city>'.$customer_order->billing_city.'</city><state>'.$customer_order->billing_state.'</state><countryCode>'.$customer_order->billing_country.'</countryCode></address></billingAddress></order></submit></paymentService>';

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
			curl_setopt($ch, CURLOPT_URL,$environment_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $worldpay_xml_data);
			$response = curl_exec($ch);
	        curl_close($ch);
	        $xml = simplexml_load_string($response);
			$error = (string)$xml->reply->error;
			$wordPayRedirectURL = (string) $xml->reply->orderStatus->reference;

			if ($error) {
				throw new Exception( __( $error, 'woo-worldpay-hosted-payment-gateway' ) );
			}

			if($wordPayRedirectURL) {
				//$woocommerce->cart->empty_cart();
				$wordpaySuccess = $this->get_return_url( $customer_order );
				$wordpayCancel = urlencode($customer_order->get_cancel_order_url_raw());
				setcookie("worldpay_url", $wordPayRedirectURL, time() + 3600, '/');
				setcookie("worldpay_success_url", $wordpaySuccess, time() + 3600, '/');
				setcookie("worldpay_cancel_url", $wordpayCancel, time() + 3600, '/');
				return array(
					'result'   => 'success',
					'redirect' => get_permalink( woocommerce_get_page_id( 'checkout' ) ),
				);
			
			}
		}
		
		// Validate fields
		public function validate_fields() {
			return true;
		}

		public function do_ssl_check() {
			if( $this->enabled == "yes" ) {
				if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
					echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
				}
			}		
		}
	}
}

?>