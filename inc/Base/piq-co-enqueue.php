<?php

/**
* @package PaymentIQ Checkout Plugin for Woocommerce
*/

/*  Enqueue means register asset files (e.g css, js) to be imported in the browser
    Wordpress splits these into the admin and the actual plugin assets.
    Basically, if you want to add a new javascript or css file, you need to add it here in order
    for it to show up as a loaded asset.
*/

 class Piq_Co_Enqueue extends Piq_Co_BaseController {
  function __construct() {}

  public function register() {
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminAssets') );
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueueAssets') );  
  }

  public function enqueueAdminAssets () {
    wp_enqueue_style( 'piqCheckoutAdminStyle', plugin_dir_url( __FILE__ ) . '../../assets/styles/piq-checkout-admin-styles.css' );
    wp_enqueue_script( 'piqCheckoutAdminScript', plugin_dir_url( __FILE__ ) . '../../assets/scripts/piq-checkout-admin.js', array('jquery'), null, true);
  }
  
  function enqueueAssets () {
    wp_enqueue_style( 'piqCheckoutScript', plugin_dir_url( __FILE__ ) . '../../assets/styles/piq-checkout-styles.css' );
    wp_enqueue_script( 'piqCheckoutScript', plugin_dir_url( __FILE__ ) . '../../assets/scripts/piq-checkout-client.js' );
    wp_enqueue_script( 'santander-widget', 'https://static.paymentiq.io/santander-calculator-widget-bootstrapper.js', false );
  }
 }