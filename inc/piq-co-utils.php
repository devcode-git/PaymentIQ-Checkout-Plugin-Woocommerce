<?php
/**
 * Util function for the plugin
 *
 * @package  PaymentIQ Checkout/utils
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Piq_Co_Utils {
	/* Function to empty cart - called from paymentiq-order-received.php  */
	public static function piq_wc_empty_cart () {
		WC()->cart->empty_cart();
	}

	/**
	 * Echoes Checkout iframe setup
 	*/
	public static function piq_wc_show_checkout () {
		$available_gateways = WC()->payment_gateways->payment_gateways();
		$payment_method     = $available_gateways['paymentiq-checkout'];
	
		$manualOrder = array(
			'status' => 'pending',
			'payment_method' => $payment_method->id,
			'billing_email' => ''
		);
	
		$cart = WC()->cart;
		$checkout = WC()->checkout();
		$order_id = $checkout->create_order($manualOrder);
		$order = wc_get_order( $order_id );
		update_post_meta($order_id, '_customer_user', get_current_user_id());
	
		$totalAmount = $order->calculate_totals();
	
		$piqClass = PIQ_CHECKOUT_WC();
		PIQ_CHECKOUT_WC()->PIQ_TOTAL_AMOUNT = $totalAmount;
		PIQ_CHECKOUT_WC()->PIQ_ORDER_ID = $order_id;
		PIQ_CHECKOUT_WC()->PIQ_RECEIPT_URL = $order->get_checkout_payment_url();
		$piqClass = PIQ_CHECKOUT_WC();
		
		do_action( 'woocommerce_checkout_create_order', $order, array() );
	
		$order->save();
	
		do_action( 'woocommerce_checkout_update_order_meta', $order_id, array() );
	}

	/* Getter functions for variables defined in paymentiq-checkout.php plugin main class  */
	
	public static function getOrderReceivedPath() {
		// https://docs.woocommerce.com/document/woocommerce-endpoints-2-1/
		$order = wc_get_order( PIQ_CHECKOUT_WC()->PIQ_ORDER_ID );
		$order->get_checkout_payment_url( $on_checkout = false );
		$orderReceivedPath = $order->get_checkout_order_received_url();
		echo $orderReceivedPath;
	}
	
	public static function getPiqMerchantId() {
		$instance = PIQ_CHECKOUT_WC();
		echo PIQ_CHECKOUT_WC()->piqMerchantId;
	}

	public static function getDidClientId() {
		$instance = PIQ_CHECKOUT_WC();
		echo PIQ_CHECKOUT_WC()->didClientId;
	}

	public static function rememberUserDevice() {
		$instance = PIQ_CHECKOUT_WC();
		$shouldRemember = PIQ_CHECKOUT_WC()->rememberUserDevice;
		// false == 'no'
		// true == 'yes' or default which is ''
		if ($shouldRemember == '' || $shouldRemember == 'yes') {
			echo 'true';
		} else {
			echo 'false';
		}
	}

	public static function getPiqEnvironment() {
		$instance = PIQ_CHECKOUT_WC();
		echo PIQ_CHECKOUT_WC()->piqEnvironment;
	}
	
	public static function getPiqButtonsColor() {
		$instance = PIQ_CHECKOUT_WC();
		echo PIQ_CHECKOUT_WC()->piqButtonsColor;
	}

	public static function getSelectedCountry() {
		$instance = PIQ_CHECKOUT_WC();
		echo $instance->piqCountry != '' ? $instance->piqCountry : strval('sweden');
	}

	public static function getSelectedLocale() {
		$instance = PIQ_CHECKOUT_WC();
		echo $instance->piqLocale != '' ? $instance->piqLocale : strval('en_GB');
	}

	public static function getPiqTotalAmount() {
		echo PIQ_CHECKOUT_WC()->PIQ_TOTAL_AMOUNT;
	}

	public static function getOrderId() {
		$instance = PIQ_CHECKOUT_WC();
		echo PIQ_CHECKOUT_WC()->PIQ_ORDER_ID;
	}
	
	public static function getPiqTxRefId() {
		$order = wc_get_order( PIQ_CHECKOUT_WC()->PIQ_ORDER_ID );
		$piqTxRefId = array_key_exists( 'transaction_id', $order ) ? $order['transaction_id'] : false;
		if ( $piqTxRefId ) {
			echo $piqTxRefId;
		} else {
			throw new Exception('PaymentIQ TxRefId missing when showing receipt');
		}
	}

	public static function getShippingTotal() {
		$order = wc_get_order( PIQ_CHECKOUT_WC()->PIQ_ORDER_ID );
		$shipping_total = $order->get_shipping_total();
		$shipping_tax = $order->get_shipping_tax();
		echo $shipping_total + $shipping_tax;
	}

	public static function getOrderItems() {
		$order = wc_get_order( PIQ_CHECKOUT_WC()->PIQ_ORDER_ID );
		$order_items = array();

		foreach ( $order->get_items() as $item_id => $item ) {
			$current_item = array();

			$name = $item->get_name();
			$number = $item->get_product_id();
			$sub_tax = $item->get_subtotal_tax();
			$sub_total = $item->get_subtotal();
			$quantity = $item->get_quantity();

			$current_item['label'] = $name;
			$current_item['price'] = $sub_total / $quantity; // sub_total is the price * quantity, meaning total per item. We want the price per product
			$current_item['vat'] = $sub_tax / $quantity;
			$current_item['quantity'] = $quantity;
			$current_item['number'] = $number;

			array_push($order_items, $current_item);
	}
		
		// return json-formatteted array of objects
		echo wp_json_encode( $order_items );
	}

	public static function updateOrderStatus ( $status ) {
		// call an action hook that main class reacts to
		do_action( 'piq_co_update_order_status', $status );
	}

	// Calculator widget options
	public static function getCalculatorWidget() {
		$instance = PIQ_CHECKOUT_WC();
		$calculatorWidget = PIQ_CHECKOUT_WC()->calculatorWidget;
		if ($calculatorWidget == '' || $calculatorWidget == 'yes') {
			return true;
		} else {
			return false;
		}
	}

	public static function getCalculatorMode() {
		$instance = PIQ_CHECKOUT_WC();
		$calculatorMode = PIQ_CHECKOUT_WC()->calculatorMode;
		if ($calculatorMode == '') {
			return 'modern';
		}
		return $calculatorMode;
	}

	public static function getCalculatorBackground() {
		$instance = PIQ_CHECKOUT_WC();
		$calculatorBackground = PIQ_CHECKOUT_WC()->calculatorBackground;
		if ($calculatorBackground == '') {
			return '#f8f8f8';
		}
		return $calculatorBackground;
	}

	public static function getCalculatorBorderColor() {
		$instance = PIQ_CHECKOUT_WC();
		$calculatorBorderColor = PIQ_CHECKOUT_WC()->calculatorBorderColor;
		if ($calculatorBorderColor == '') {
			return '#cacaca';
		}
		return $calculatorBorderColor;
	}

	public static function getCalculatorTextColor() {
		$instance = PIQ_CHECKOUT_WC();
		$calculatorTextColor = PIQ_CHECKOUT_WC()->calculatorTextColor;
		if ($calculatorTextColor == '') {
			return '#333333';
		}
		return $calculatorTextColor;
	}

	public static function getCalculatorBorderRadius() {
		$instance = PIQ_CHECKOUT_WC();
		$calculatorBorderRadius = PIQ_CHECKOUT_WC()->calculatorBorderRadius;
		if ($calculatorBorderRadius == '') {
			return '4px';
		}
		return $calculatorBorderRadius;
	}

	public static function getCalculatorRaised() {
		$instance = PIQ_CHECKOUT_WC();
		$calculatorRaised = PIQ_CHECKOUT_WC()->calculatorRaised;
		if ($calculatorRaised == '') {
			return 0;
		}
		return $calculatorRaised;
	}

 } //end of class


add_action('wp_ajax_ACTION_NAME', 'handlePiqCheckoutTxStatusNotification');
add_action( 'wp_ajax_nopriv_ACTION_NAME', 'handlePiqCheckoutTxStatusNotification' );
function handlePiqCheckoutTxStatusNotification() {
	check_ajax_referer('piq_co_tx_status_update_nonce');
	
	$status	= isset($_POST['status']) ? trim( sanitize_text_field( $_POST['status'] ) ) : "";
	$orderId	= isset($_POST['orderId']) ? trim( sanitize_text_field( $_POST['orderId'] ) ) : "";

	$args = array (
    'status' => $status,
    'orderId' => $orderId, // max posts
	);
	
	do_action('piq_co_handle_transaction_status_update', $args);

	$response	= array();
	$response['message']	= "Successfull Request";
	echo json_encode($response);
}
