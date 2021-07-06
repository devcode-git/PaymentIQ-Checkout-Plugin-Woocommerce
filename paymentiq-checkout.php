<?php

/**
 * @package paymentiq-checkout
 */
 /* 
 Plugin Name: PaymentIQ Checkout
 Plugin URI: https://docs.paymentiq.io/
 Description: PaymentIQ Checkout for Woocommerce
 Version: 1.0.4
 Author: PaymentIQ/Bambora
 Author URI: https://www.bambora.com/payment-for-gaming/paymentiq/
 License: GPLv2 or later
 Text Domain: paymentiq-checkout

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// If not inside wordpress - die right away
defined( 'ABSPATH' ) or die( 'Hey you can\t access this file, you silly human');

/* Using composer - autoload for simpler importing of classes */
if ( file_exists(  dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
  require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

// Global path for importing files & classes
define( 'PIQ_WC_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

/* Activate and Deactivation Hook handlers 
   A Wordpress plugin has an activation & deactivation hook - we can call functions/classes to be triggered
   Need to set these up to start with
*/
include_once PIQ_WC_PLUGIN_PATH . '/inc/Base/piq-co-activate.php';
include_once PIQ_WC_PLUGIN_PATH . '/inc/Base/piq-co-deactivate.php';

function activatePIQCheckout () {
  Piq_Co_Activate::activate(); // inc/Base/piq-co-activate.php
}
register_activation_hook( __FILE__, 'activatePIQCheckout' );

function deactivatePIQCheckout () {
  Piq_Co_Deactivate::deactivate(); // inc/Base/piq-co-deactivate.php
}
register_deactivation_hook( __FILE__, 'deactivatePIQCheckout' );

/* Hook for when plugins have loaded -> Our way of knowing when to kick things of
*/
/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
  add_action( 'plugins_loaded', 'initPIQCheckout', 0 );
}

// add_action( 'plugins_loaded', 'initPIQCheckout', 0 );

function initPIQCheckout () {
  // bow out early if we don't have WooCommerce yet

  if ( ! class_exists( 'WC_Payment_Gateway' ) || class_exists( 'PIQCheckoutWoocommerce' ) ) {
    return;
  }

  /*
    Imports of classes and helper files
  */

  include_once PIQ_WC_PLUGIN_PATH . '/inc/piq-co-init.php';
  include_once PIQ_WC_PLUGIN_PATH . '/inc/piq-co-admin-utils.php';
  include_once PIQ_WC_PLUGIN_PATH . '/inc/piq-co-utils.php';
  include_once PIQ_WC_PLUGIN_PATH . '/inc/Api/piq-co-admin-api.php';
  include_once PIQ_WC_PLUGIN_PATH . '/inc/Base/piq-co-setup.php';

  /*  Initialize PaymentIQ Checkout and extend it with WC_Payment_Gateway
      After init, call the register function which is turn calls the Init class.
      Check if our Init class exists (/Inc/Init.php)
      If it does -> Start it up
  */

  class PIQCheckoutWoocommerce extends WC_Payment_Gateway {

    /**
		 * The instance of this class
		 *
		 * @var $_instance
		 */
		// protected static $instance;
		private static $_instance;

    public function __construct () {
      $this->id = 'paymentiq-checkout';
      $this->method_title = 'PaymentIQ Checkout';
      $this->icon = WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/bambora-logo.svg';
      $this->has_fields = false;
      $this->piqMerchantId = $this->get_option( 'piqMerchantId' );
      $this->didClientId = $this->get_option( 'didClientId' );
      $this->piqCountry = strval($this->get_option( 'piqCountry' ));
      $this->piqLocale = strval($this->get_option( 'piqLocale' ));
      $this->piqEnvironment = strval($this->get_option( 'piqEnvironment' ));
      $this->piqButtonsColor = strval($this->get_option( 'piqButtonsColor' ));
      $this->rememberUserDevice = strval($this->get_option( 'rememberUserDevice' ));
      $this->captureOnStatusComplete = strval($this->get_option( 'captureOnStatusComplete' ));
      $this->calculatorWidget = strval($this->get_option( 'calculatorWidget' ));
      $this->calculatorMode = strval($this->get_option( 'calculatorMode' ));
      $this->calculatorBackground = strval($this->get_option( 'calculatorBackground' ));
      $this->calculatorBorderColor = strval($this->get_option( 'calculatorBorderColor' ));
      $this->calculatorTextColor = strval($this->get_option( 'calculatorTextColor' ));
      $this->calculatorBorderRadius = strval($this->get_option( 'calculatorBorderRadius' ));
      $this->calculatorRaised = strval($this->get_option( 'calculatorRaised' ));
      $this->PIQ_TOTAL_AMOUNT = null;
      $this->PIQ_ORDER_ID = null;

      /* Register base stuff - enque scripts and styles */
      $this->register();
      
      $this->supports = Piq_Co_Setup::registerSupports();
      $this->form_fields = Piq_Co_Setup::registerFormFields();

      // Load the settings.!
      $this->init_settings();

      // Initilize PaymentIQ Settings
      $this->initCheckoutSettings();

      $this->init();
    }

    public static function get_instance() {
      // return self::$instance;
      $current = self::$_instance;
      if (!isset( self::$_instance ) ) {
        self::$_instance = new self();
      }
      return self::$_instance;
		}
    
    
    public function register () {
      if ( class_exists( 'Piq_Co_Init' ) ) {
        Piq_Co_Init::registerServices();
      }
    }

    public function init () {
      add_filter( 'woocommerce_payment_gateways', array( $this, 'addPaymentIQCheckout' ) );
      add_filter( 'wc_get_template', array( $this, 'overrideTemplate' ), 999, 2 );
    }

    public function addPaymentIQCheckout ( $methods ) {
      $methods[] = 'PIQCheckoutWoocommerce'; // name of the main class
        return $methods;
    }

    /*
      WooCommerce hook for when their templates are rendered. Here we can override
      their with ours - so in our case we render our checkout instead of their KYC form.
    
      @param string $template      Template.
	    @param string $template_name Template name.
	    @return string
    */
    public function overrideTemplate( $template, $template_name ) {
      // echo $template_name prints out the name of every template used.
      // by this we can identity what templates we need to make our own versions
      // of to display our custom stuff
      // echo $template_name;
      switch ($template_name) {
        case 'checkout/payment-method.php':
          return '';
        case 'single-product/meta.php':
          $template = PIQ_WC_PLUGIN_PATH . '/templates/Product/paymentiq-product-meta.php';
          return $template;
        case 'checkout/form-checkout.php':
          $template = PIQ_WC_PLUGIN_PATH . '/templates/Checkout/paymentiq-checkout.php';
          return $template;
        case 'checkout/thankyou.php':
          // Todo: Reworkd paymentiq-order-received to show our own thank-you page - based on the cashier
          $template = PIQ_WC_PLUGIN_PATH . '/templates/Checkout/paymentiq-order-received.php';
          return $template;
        default:
          return $template;
      }
    }

    public function initCheckoutSettings () {
      // Define user set variables!
      $this->enabled = array_key_exists( 'enabled', $this->settings ) ? $this->settings['enabled'] : 'yes';
      $this->title = array_key_exists( 'title', $this->settings ) ? $this->settings['title'] : 'PaymentIQ Checkout';
      $this->description = array_key_exists( 'description', $this->settings ) ? $this->settings['description'] : 'Pay using PaymentIQ Checkout';
      $this->merchantId = array_key_exists( 'merchant', $this->settings ) ? $this->settings['merchantId'] : '';
      $this->accesstoken = array_key_exists( 'accesstoken', $this->settings ) ? $this->settings['accesstoken'] : '';
    }

    public function init_hooks() {
      // Actions!
      add_action('woocommerce_checkout_fields', array( $this, 'disable_billing_shipping' ) );
      
      // Toggle of emails - doesn't work locally
      if ($this->piqEnvironment === 'development') {
        add_action( 'woocommerce_email', array( $this, 'unhook_those_pesky_emails' ) );
      }

      if( is_admin() ) {
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        
        add_action( 'add_meta_boxes', array( $this, 'paymentiq_checkout_meta_boxes' ) );
        
        add_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'paymentiq_checkout_edit_order_after_refunds' ) );

        /* Inject manual action buttons in the admin panel in order details */
        add_action('woocommerce_order_item_add_action_buttons', array( $this, 'paymentiq_checkout_render_custom_order_action_buttons' ) );

        /* Catch manual redirects that contain 'paymentiq_checkout_action' -> manual admin action buttons  */
        add_action( 'wp_before_admin_bar_render', array( $this, 'paymentiq_checkout_actions' ) );

        /* Render admin notices to the user */
        add_action( 'admin_notices', array( $this, 'paymentiq_checkout_admin_notices' ) );

        if($this->captureOnStatusComplete === 'yes') {
          add_action( 'woocommerce_order_status_completed', array( $this, 'paymentiq_checkout_order_status_completed' ) );
        }
      }
      
      /* Hook when a woo order is cancelled - if the order has a piqTxId - do a void (cancel in PIQ) */
      add_action('woocommerce_order_status_cancelled', array( $this, 'paymentiq_checkout_handle_order_cancelled' ) );
     
      /* When we receive a txStatus from PIQ Checkout
         This is a self-triggered action when the PaymentIQ cashier gets the success-callback */
      add_action( 'piq_co_handle_transaction_status_update', array( $this, 'handle_transaction_status_update' ) );
    }

    /*
      When developing locally, disable trying to send emails
    */
    public function unhook_those_pesky_emails( $email_class ) {

      /**
       * Hooks for sending emails during store events
       **/
      remove_action( 'woocommerce_low_stock_notification', array( $email_class, 'low_stock' ) );
      remove_action( 'woocommerce_no_stock_notification', array( $email_class, 'no_stock' ) );
      remove_action( 'woocommerce_product_on_backorder_notification', array( $email_class, 'backorder' ) );
      
      // New order emails
      remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
      remove_action( 'woocommerce_order_status_pending_to_completed_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
      remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
      remove_action( 'woocommerce_order_status_failed_to_processing_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
      remove_action( 'woocommerce_order_status_failed_to_completed_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
      remove_action( 'woocommerce_order_status_failed_to_on-hold_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
      
      // Processing order emails
      remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger' ) );
      remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger' ) );
      
      // Completed order emails
      remove_action( 'woocommerce_order_status_completed_notification', array( $email_class->emails['WC_Email_Customer_Completed_Order'], 'trigger' ) );
        
      // Note emails
      remove_action( 'woocommerce_new_customer_note_notification', array( $email_class->emails['WC_Email_Customer_Note'], 'trigger' ) );
    }

     /**
     * Show messages in the Administration
     */
    public function paymentiq_checkout_admin_notices(){
      Piq_Co_Admin_Utils::echo_admin_notices();
    }

    function disable_billing_shipping( $fields ){
      $fields[ 'billing' ] = array();
      $fields[ 'shipping' ] = array();
      return $fields;
    }

    public function paymentiq_checkout_meta_boxes() {
      global $post;
      $order_id = $post->ID;
      if( !$this->module_check( $order_id ) ) {
          return;
      }

      add_meta_box(
          'paymentiq-checkout-payment-actions',
          __( 'PaymentIQ Checkout', 'paymentiq-checkout' ),
          array( &$this, 'paymentiq_checkout_meta_box_payment' ),
          'shop_order',
          'side',
          'high'
      );
    }

    public function paymentiq_checkout_meta_box_payment() {
      global $post;
      $order_id = $post->ID;
      $order = wc_get_order( $order_id );
      if ( !empty( $order ) ) {
        $captured_amount = $order->get_meta('piq_captured_amount');
        
        $j_order = json_decode(wc_get_order( $order_id ), true);
        $piqTxId = array_key_exists( 'transaction_id', $j_order ) ? $j_order['transaction_id'] : false;

        $piqPsp = $order->get_meta('piq_tx_psp');
        $piqTxType = $order->get_meta('piq_tx_type');
        $piq_capture_id = $order->get_meta('piq_capture_tx_id');

        $html = '<div class="paymentiq-checkoutinfo">';
        
        if ($piqTxId && $piqTxId !== '') {
          $html .= '<div class="paymentiq-checkout-id">';
          $html .= '<p><b>' . __( 'Transaction ID', 'paymentiq-checkout' ) . '</b>: ' . $piqTxId . '</p>';
          $html .= '</div>';
        }
        
        $html .= '<div class="paymentiq-checkout-psp">';
        $html .= '<p><b>' . __( 'Payment Type', 'paymentiq-checkout' ) . '</b>: ' . $piqPsp . '</p>';
        $html .= '</div>';
        
        $html .= '<div class="paymentiq-checkout-tx-type">';
        $html .= '<p><b>' . __( 'Payment Tx type', 'paymentiq-checkout' ) . '</b>: ' . $piqTxType . '</p>';
        $html .= '</div>';

        if ($captured_amount && $captured_amount !== '') {
          $html .= '<div class="paymentiq-checkout-info-overview">';
          $html .= '<p><b>' . __( 'Captured', 'paymentiq-checkout' ) . '</b>: ' . wc_format_localized_price( $captured_amount ) . 'kr' . '</p>';
          $html .= '</div>';
        }
        if ($piq_capture_id && $piq_capture_id !== '') {
          $html .= '<div class="paymentiq-checkout-info-overview">';
          $html .= '<p><b>' . __( 'Capture Id', 'paymentiq-checkout' ) . '</b>: ' . $piq_capture_id . '</p>';
          $html .= '</div>';
        }

        $html .= '</div>';
        echo ent2ncr( $html );
        
          // }
      } else {
        $message = sprintf( __( 'Could not load the order with order id %s', 'paymentiq-checkout'), $order_id );
        echo $message;
      }
      
    }

    function paymentiq_checkout_edit_order_after_refunds ( $order_id ) {
      $order = wc_get_order( $order_id );
      $captured_amount = $order->get_meta('piq_captured_amount');
      if ($captured_amount) {
        $html = '<table class="wc-order-totals updated_yet" style="border-top: 1px solid #999; margin-top:12px; padding-top:12px">
          <div class="clear"></div>
          <tbody>
            <tr>
              <td class="label captured-total">Captured:</td>
              <td width="1%"></td>
              <td class="total captured-total">-<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">kr</span>' . wc_format_localized_price( $captured_amount ) . '</bdi></span></td>
            </tr>
          </tbody>
          <div class="clear"></div>
        </table>
        <div class="clear"></div>';
        echo ent2ncr( $html );
      }
    }

    /*
      Woo allows for an action hook to include custom html in the action buttons row for an order in the admin view
      We want to add a "Capture" button for orders that have only been authorized
    */
    function paymentiq_checkout_render_custom_order_action_buttons ($order) {   
        // We only render the capture button for certain PIQ statuses, where a capture is allowed and availalbe. This by checking the
        // PIQ txStatus in the order's meta_data

        // $allowed_piq_capture_statuses = ['SUCCESS_WAITING_CAPTURE', 'SUCCESS_WAITING_AUTO_CAPTURE'];
        // $piq_tx_status = $order->get_meta('piq_tx_status');
        if (Piq_Co_Admin_Utils::supports_capture( $order )) {
          // In piq-checkout-admin.js we have a registered click handler to the id of this button
          // That one is triggered and deals with the rest of the logic for displaying & managing the capture

          $piq_captured_amount = $order->get_meta('piq_captured_amount');
          echo '<button id="paymentiq-checkout-manual-capture" type="button" class="button add-special-item" data-piq_captured_amount="'. esc_attr($piq_captured_amount)  .'" data-order_id="'. esc_attr($order->get_id())  .'" >Capture charge</button>';
        }
    } 

    /*
     Custom action hook we trigger when we get a javascript callback in the checkout/cashier
     $args are the url-query params containing

     $status -> redirect status (success, pending, failure, cancel)
     $order_id -> Woocommerce order id
    */
    public function handle_transaction_status_update ( $args ) {
      ob_clean();
      ob_start();

      $status = $args['status'];
      $order_id = $args['orderId'];

      $payment_methods = WC()->payment_gateways->get_available_payment_gateways();
      $result = $payment_methods[ 'paymentiq-checkout' ]->process_payment( $order_id );
      $resultUrl = $result['redirect'];

      // Never got this redirect to work - asked wordpress/woocommerce support without any luck.
      // The redirect used is from the javascript - this by passing in the orderId + orderKey when the cashier sends the success-callback
      // header( 'Location:' . $result['redirect'] );
      // exit;
    }

    function process_payment( $order_id ) {
      global $woocommerce;
      $order = new WC_Order( $order_id );
      $order->payment_complete();
      
      $orderUrl = $order->get_checkout_order_received_url();
      return array(
          'result' => 'success',
          'redirect' => $orderUrl
      );
    }

    /**
     * PaymentIQ Checkout Actions
     * To deal with custom features in the admin view - we do get redirects and pass a url parameter called
     * piq_checkout_action
     * 
     * If the url contains that parameter - we catch and handle it here.
     */
    public function paymentiq_checkout_actions() {
      if ( isset( $_GET['paymentiq_checkout_action'] ) ) {
          $order_id = wc_sanitize_order_id( $_GET['post'] );
          $amount = sanitize_text_field( $_GET['amount'] );
          $action = sanitize_text_field( $_GET['paymentiq_checkout_action'] );

          $action_result = null;
          try {
              switch ( $action ) {
                case 'capture':
                  $action_result = $this->paymentiq_checkout_capture_payment($order_id, $amount);
                  break;
              }
          }
          catch ( Exception $ex ) {
              $action_result = new WP_Error( 'bambora_online_checkout_error', $ex->getMessage() );
          }

          if( is_wp_error( $action_result ) ) {
              $errors = $action_result->errors;
              $message = $errors['paymentiq_checkout_error'][0];
              Piq_Co_Admin_Utils::add_admin_notices(Piq_Co_Admin_Utils::ERROR, $message);
          } else {
              global $post;
              $message = sprintf( __( 'The %s action was a success for order %s', 'paymentiq-checkout' ), $action, $order_id );
              Piq_Co_Admin_Utils::add_admin_notices(Piq_Co_Admin_Utils::SUCCESS, $message);
              $url = admin_url( 'post.php?post=' . $post->ID . '&action=edit' );
              wp_safe_redirect( $url );
          }
      }
    }

    /*
      $order_id -> Woo order object
      $amount -> Custom order to capture, set by the user
    */
    public function paymentiq_checkout_capture_payment ( $order_id, $amount ) {
      $order = wc_get_order( $order_id );
      $reason = 'Capture PaymentIQ from WooCommerce, orderId: ' . $order_id;

      $admin_api = new Piq_Co_Admin_Api();
      $capture_result = $admin_api->capture_transaction( $order_id, $amount, $reason );
      if ( is_wp_error( $capture_result ) ) {
        return $capture_result; // error was returned from captureTransaction
      } else {
          $message = sprintf( __( 'The Capture action was a success for order %s', 'bambora-online-checkout' ), $order_id );
          Piq_Co_Admin_Utils::add_admin_notices(Piq_Co_Admin_Utils::SUCCESS, $message);
          Piq_Co_Admin_Utils::echo_admin_notices();
          return $capture_result; // true if successful
      }
      return new WP_Error( 'paymentiq_checkout_error', 'Capture not yet implemented');
    }

    public function process_refund ( $order_id, $amount = null, $reason = '' ) {
      /* 
         We end up here when an admin manually pick an order and click refund with PaymentIQ Checkout
         We receive orderId, amount set by admin + optional note
         From here, we wanna make sure we have the info we need (input parameters). Then extract the paymentiq txId
         and call PaymentIQ Admin-api and make a refund. If everything goes ok we return true which will set the order as refunded in WooCommerce.
      */

      if ( ! isset( $amount ) ) {
        return true;
      }

      $admin_api = new Piq_Co_Admin_Api();
      $refund_result = $admin_api->refund_transaction( $order_id, $amount, $reason );

      if ( is_wp_error( $refund_result ) ) {
          return $refund_result;
      } else {
          $message = sprintf( __( 'The Refund action was a success for order %s', 'bambora-online-checkout' ), $order_id );
          Piq_Co_Admin_Utils::add_admin_notices(Piq_Co_Admin_Utils::SUCCESS, $message);
          return $refund_result; // true if successful
      }
      return new WP_Error( 'paymentiq_checkout_error', 'Refund not yet implemented');
    }

    /*
      $order_id -> woo orderId

      Triggered when an order in WooCommerce is cancelled.
      Check if a PIQ-txId exists on the order. If it does, then we have a PIQ transaction that needs to be voided 
    */
    public function paymentiq_checkout_handle_order_cancelled ( $order_id ) {
      $order = wc_get_order( $order_id );
      $reason = 'Basic void PaymentIQ';

      $admin_api = new Piq_Co_Admin_Api();
      $void_result = $admin_api->void_transaction( $order_id, $reason );
      if ( is_wp_error( $void_result ) ) {
        return $void_result; // error was returned from captureTransaction
      } else {
          $message = sprintf( __( 'The void action was a success for order %s', 'bambora-online-checkout' ), $order_id );
          Piq_Co_Admin_Utils::add_admin_notices(Piq_Co_Admin_Utils::SUCCESS, $message);
          Piq_Co_Admin_Utils::echo_admin_notices();
          return $void_result; // true if successful
      }
      return new WP_Error( 'paymentiq_checkout_error', 'Void not yet implemented');
    }

    /**
     * Capture the payment on order status completed
     * @param mixed $order_id
     */
    public function paymentiq_checkout_order_status_completed( $order_id ){
      $order = wc_get_order( $order_id );

      if( !$this->module_check( $order_id ) || !Piq_Co_Admin_Utils::supports_capture( $order ) ) {
          return;
      }

      $amount = $order->get_total();
      $capture_result = $this->paymentiq_checkout_capture_payment( $order_id, $amount );

      if( is_wp_error( $capture_result ) ) {
        $errors = $capture_result->errors;
        $message = $errors['paymentiq_checkout_error'][0];
        // $this->_boc_log->add($message);
        Piq_Co_Admin_Utils::add_admin_notices(Piq_Co_Admin_Utils::ERROR, $message);
      } else {
        global $post;
        $message = sprintf( __( 'The %s action was a success for order %s', 'paymentiq-checkout' ), $action, $order_id );
        Piq_Co_Admin_Utils::add_admin_notices(Piq_Co_Admin_Utils::SUCCESS, $message);
        $url = admin_url( 'post.php?post=' . $post->ID . '&action=edit' );
        wp_safe_redirect( $url );
      }
    }

    /*
    * Sanity check - is the current payment method paymentiq-checkout?
    *
    */
    public function module_check($order_id) {
      $payment_method = get_post_meta( $order_id, '_payment_method', true );
      return $this->id === $payment_method;
    }

  
  } /* End of class  */

  /* Create instance of our plugin and register hooks*/
  PIQCheckoutWoocommerce::get_instance();
  PIQCheckoutWoocommerce::get_instance()->init_hooks();
  

} /* End of initPIQCheckout */

function PIQ_CHECKOUT_WC() { // phpcs:ignore
	return PIQCheckoutWoocommerce::get_instance();
}
