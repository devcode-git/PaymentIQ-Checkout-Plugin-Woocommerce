<?php

/**
 * @package PaymentIQ Checkout Plugin for Woocommerce
 * 
 * Adds settings input fields to the admin page of PaymentIQ Checkout
 * 
 * Using global variabels:
 * @plugin -> Name reference to our plugin (See BaseController.php)
 */

class Piq_Co_Setup {

  /**
   * What does this plugin support? Woocommerce use this to list our support in the different categories
   * 
   */
  static function registerSupports () {
    return array(
        'products',
        'refunds',
        'capture',
        'voids',
        'subscription_cancellation',
        'subscription_suspension',
        'subscription_reactivation',
        'subscription_amount_changes',
        'subscription_date_changes',
        'subscription_payment_method_change_customer',
        'multiple_subscriptions'
      );
  }
  
  /**
   * Fields displayed in the Settings view of the PaymentIQ Checkout plugin
   * Each of these fields are also (manually) added as properties to the main class
   * 
  */
  static function registerFormFields () {
    return array(
      'enabled' => array(
          'title' => 'Activate module',
          'type' => 'checkbox',
          'label' => 'Enable PaymentIQ Checkout as a payment option.',
          'default' => 'yes'
      ),
      'piqMerchantId' => array(
        'title' => 'PaymentIQ Merchant ID',
        'type' => 'text',
        'description' => 'The id identifying your PaymentIQ merchant account.',
        'default' => ''
      ),
      'didClientId' => array(
        'title' => 'Devcode Identity client id',
        'type' => 'text',
        'description' => 'The client id used with Devcode identity. This is the service that keeps track of the user, allows quick-id and login in with identify providers.',
        'default' => ''
      ),
      'piqCountry' => array(
        'title' => 'Country',
        'type' => 'select',
        'description' => 'Select country to select what login providers to display',
        'default' => 'SE',
        'options' => array(
          'SE' => 'Sweden',
          'NO' => 'Norway',
          'DK' => 'Denmark',
          'FI' => 'Finland',
          'demo' => 'Demo (enable all login providers)',
        )
      ),
      'piqLocale' => array(
        'title' => 'Language',
        'type' => 'select',
        'description' => 'What language should the Checkout be displayed in?',
        'default' => 'en_GB',
        'options' => array(
          'en_GB' => 'English (en_GB)',
          'no_NO' => 'Norwegian (no_NO)',
          'sv_SE' => 'Swedish (sv_SE)',
          'da_DK' => 'Danish (da_DK)',
          'fi_FI' => 'Finnish (fi_FI)',
        )
      ),
      'piqEnvironment' => array(
        'title' => 'Environment',
        'type' => 'select',
        'description' => 'Target test or production environment of PaymentIQ',
        'default' => 'production',
        'options' => array(
          'production' => 'production',
          'test' => 'test'
        )
      ),
      'rememberUserDevice' => array(
        'title' => 'Remember user device',
        'type' => 'checkbox',
        'description' => 'Should we remember a returning user\'s device id (unique id for every user). If enabled, the user can "quick-login" with just their zip/email',
        'default' => 'yes'
      ),
      'piqApiClientId' => array(
        'title' => 'PaymentIQ API client id',
        'type' => 'text',
        'description' => 'The auth client Id',
        'default' => ''
      ),
      'piqApiClientSecret' => array(
        'title' => 'PaymentIQ API client secret',
        'type' => 'password',
        'description' => 'The auth client secret',
        'default' => ''
      ),
      'captureOnStatusComplete' => array(
        'title' => 'Capture on order completed',
        'type' => 'checkbox',
        'description' => 'When this is enabled the full payment will be captured when the order status changes to Completed. If not enabled, manual capture is available in the order details.',
        'default' => 'no'
      ),
      'piqButtonsColor' => array(
        'title' => 'PaymentIQ Checkout Buttons color',
        'type' => 'text',
        'description' => 'Hex code of buttons',
        'default' => '#9C72BC'
      ),
      'calculatorWidget' => array(
        'title' => 'Enable loan calculator',
        'type' => 'checkbox',
        'description' => 'Displays a loan calculator at product page',
        'default' => 'yes'
      ),
      array(
        'title' => __( 'Loan calculator options' ),
        'type'  => 'title',
        'desc'  => __( 'The following options affect how prices are displayed on the frontend.', 'woocommerce' ),
      ),
      'calculatorMode' => array(
        'title'    => 'Mode',
        'desc'     => 'This sets the display mode, classic or modern.',
        'default'  => 'modern',
        'type'     => 'select',
        'class'    => 'wc-enhanced-select',
        'desc_tip' => true,
        'options'  => array (
          'modern'        => 'Modern',
					'classic'       => 'Classic',
        ),
      ),
      'calculatorBackground' => array(
        'title' => 'Calculator background color',
        'type' => 'text',
        'description' => 'Hex, RGB or name code',
        'default' => '#f8f8f8'
      ),
      'calculatorBorderColor' => array(
        'title' => 'Calculator border color',
        'type' => 'text',
        'description' => 'Hex, RGB or name code',
        'default' => '#cacaca'
      ),
      'calculatorTextColor' => array(
        'title' => 'Calculator text color',
        'type' => 'text',
        'description' => 'Hex, RGB or name code',
        'default' => '#333333'
      ),
      'calculatorBorderRadius' => array(
        'title' => 'Calculator border radius',
        'type' => 'text',
        'description' => 'Border radius in any css unit',
        'default' => '4px'
      ),
      'calculatorRaised' => array(
        'title' => 'Calculator raised level',
        'type'     => 'select',
        'description' => 'Calculator may be formatted to raise above the page',
        'default' => 0,
        'options'  => array (
          0        => 'None',
					1        => 'Slightly',
          2        => 'Raised',
        ),
      ),
    );
  }

}