<?php
/**
 * Single Product Meta
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/meta.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

require_once(dirname(__FILE__) . '/../../inc/piq-co-utils.php');
$Piq_Co_Utils = new Piq_Co_Utils();
$calculatorWidget = $Piq_Co_Utils->getCalculatorWidget();
$price = intval($product->get_price());
$minPrice = 1990; // If amount is bigger than 1990 NOK and the setting is turned on.
// echo $product; // echo this one to see product data
?>

<?php if($calculatorWidget && $price >= $minPrice) : ?>

<div class="product_meta">
  
  <!-- PaymentIQ Developed Widget -->
  <div id='santander-checkout-widget'></div>
  <script>
  function renderWidget () {
    const checkout_widget = new window.SANTANDER_CHECKOUT_WIDGET(
      'santander-checkout-widget',
      {
        mode: '<?php echo $Piq_Co_Utils->getCalculatorMode(); ?>',
        environment: 'production',
        iframeHeight: '250px',
        theme: {
          raised: '<?php echo intval($Piq_Co_Utils->getCalculatorRaised()); ?>',
          background: '<?php echo $Piq_Co_Utils->getCalculatorBackground(); ?>',
          border: '<?php echo $Piq_Co_Utils->getCalculatorBorderColor(); ?>',
          text: '<?php echo $Piq_Co_Utils->getCalculatorTextColor(); ?>',
          borderRadius: '<?php echo $Piq_Co_Utils->getCalculatorBorderRadius(); ?>',
        },
        paymentDetails: {
          loanAmount: <?php echo $price ?>
        }
      }
    );
  }

  function initWidget () {
    if (window.SANTANDER_CHECKOUT_WIDGET) {
      renderWidget()
    } else {
      setTimeout(() => {
        initWidget()
      }, 150);
    }
  }
  initWidget()
  </script>


  <!-- END PaymentIQ Developed Widget -->

	<?php do_action( 'woocommerce_product_meta_start' ); ?>

	<?php if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) : ?>

		<span class="sku_wrapper"><?php esc_html_e( 'SKU:', 'woocommerce' ); ?> <span class="sku"><?php echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/A', 'woocommerce' ); ?></span></span>

	<?php endif; ?>

	<?php echo wc_get_product_category_list( $product->get_id(), ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', count( $product->get_category_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?>

	<?php echo wc_get_product_tag_list( $product->get_id(), ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', count( $product->get_tag_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?>

	<?php do_action( 'woocommerce_product_meta_end' ); ?>

</div>

<?php endif; ?>