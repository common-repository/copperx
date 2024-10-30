<?php
/**
 * Copperx Woocommerce Payment Gateway
 *
 * @package Copperx
 */

/*
Plugin Name:  Copperx
Plugin URI:   https://github.com/CopperxHQ/woocommerce-plugin/
Description:  Accept crypto payments in just 5 minutes. (https://copperx.io)
Version:      1.6.0
Author:       Copperx
Author URI:   https://copperx.io
License:      GPLv3+
License URI:  https://www.gnu.org/licenses/gpl-3.0.html
Text Domain:  copperx

WC requires at least: 5.0
WC tested up to: 6.5

Copperx is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

Copperx is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Copperx. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_COPPERX_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

function copperx_init_gateway() {
	// If WooCommerce is available, initialism WC parts.
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		require_once 'class-wc-gateway-copperx.php';
		add_action( 'init', 'copperx_wc_register_custom_order_status' );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'plugin_action_links');
		add_filter( 'woocommerce_valid_order_statuses_for_payment', 'copperx_wc_status_valid_for_payment', 10, 2 );
		add_action( 'copperx_check_orders', 'copperx_wc_check_orders' );
		add_filter( 'woocommerce_payment_gateways', 'copperx_wc_add_copperx_class' );
		add_filter( 'wc_order_statuses', 'copperx_wc_add_status' );
		add_action( 'woocommerce_order_details_after_order_table', 'copperx_order_meta_general' );
	}
}
add_action( 'plugins_loaded', 'copperx_init_gateway' );

/**
 * Declare compatibility with custom_order_tables
*/
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
 * Declare compatibility with cart_checkout_blocks feature
*/
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil'))
    {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action('woocommerce_blocks_loaded', 'copperx_woocommerce_block_support');

/**
 * Custom function to register a payment method type
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
function copperx_woocommerce_block_support()
{
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType'))
    {
        require_once dirname( __FILE__ ) . '/includes/class-wc-checkout-block-gateway-copperx.php';

        add_action(
          'woocommerce_blocks_payment_method_type_registration',
          function(PaymentMethodRegistry $payment_method_registry) {
            $container = Automattic\WooCommerce\Blocks\Package::container();
            $container->register(
                WC_Checkout_Block_Gateway_Copperx::class,
                function() {
                    return new WC_Checkout_Block_Gateway_Copperx();
                }
            );
            $payment_method_registry->register($container->get(WC_Checkout_Block_Gateway_Copperx::class));
          },
          5
        );
    }
}


// Add plugin action links.

function plugin_action_links( $links ) {
	$plugin_links = [
		'<a href="admin.php?page=wc-settings&tab=checkout&section=copperx">' . esc_html__( 'Settings', 'copperx' ) . '</a>',
	];
	return array_merge( $plugin_links, $links );
}


// Setup cron job.

// Add cron interval of 1800 seconds (30 minutes)
add_filter( 'cron_schedules', 'copperx_add_cron_interval' );

function copperx_add_cron_interval( $schedules ) {
   $schedules['thirtyminutes'] = [
	   'interval' => 1800,
	   'display' => __( 'Every 30 Minutes' )
   ];
   return $schedules;
}

function copperx_activation() {
	if ( ! wp_next_scheduled( 'copperx_check_orders' ) ) {
		wp_schedule_event( time(), 'thirtyminutes', 'copperx_check_orders' );
	}
}
register_activation_hook( __FILE__, 'copperx_activation' );

function copperx_deactivation() {
	wp_clear_scheduled_hook( 'copperx_check_orders' );
}
register_deactivation_hook( __FILE__, 'copperx_deactivation' );



// woocommerce

function copperx_wc_add_copperx_class($methods) {
	$methods[] = 'WC_Gateway_Copperx';
	return $methods;
}


function copperx_wc_check_orders() {
	$gateway = WC()->payment_gateways()->payment_gateways()['copperx'];
	return $gateway->check_orders();
}



/**
 * Register new status with ID "wc-blockchainpending" and label "Blockchain Pending",
 * Register new status with ID "wc-incompletepayment" and label "Incomplete Payment"
 */
function copperx_wc_register_custom_order_status() {
	register_post_status( 'wc-blockchainpending', [
		'label'                     => __( 'Blockchain Pending', 'copperx' ),
		'public'                    => true,
		'show_in_admin_status_list' => true,
		/* translators: WooCommerce order count in blockchain pending. */
		'label_count'               => _n_noop( 'Blockchain pending <span class="count">(%s)</span>', 'Blockchain pending <span class="count">(%s)</span>', 'copperx' ),
	] );

	register_post_status( 'wc-incompletepayment', [
		'label'                     => __( 'Incomplete Payment', 'copperx' ),
		'public'                    => true,
		'show_in_admin_status_list' => true,
		/* translators: WooCommerce order count in Incomplete Payment. */
		'label_count'               => _n_noop( 'Incomplete Payment <span class="count">(%s)</span>', 'Incomplete Payment <span class="count">(%s)</span>', 'copperx' ),
	] );

}

/**
 * Register wc-blockchainpending, wc-incompletepayment  status as valid for payment.
 *
 * @param status $statuses Array of statuses that are valid for payment.
 * @param WC_Order $order Order object.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function copperx_wc_status_valid_for_payment( $statuses, $order ) {
	$statuses[] = 'wc-blockchainpending';
	$statuses[] = 'wc-incompletepayment';
	return $statuses;
}

/**
 * Add registered status to list of WC Order statuses
 *
 * @param array $wc_statuses_arr Array of all order statuses on the website.
 */
function copperx_wc_add_status( $wc_statuses_arr ) {
	$new_statuses_arr = [];

	// Add new order status after payment pending.
	foreach ( $wc_statuses_arr as $id => $label ) {
		$new_statuses_arr[ $id ] = $label;

		if ( 'wc-pending' === $id ) {  // after "Payment Pending" status.
			$new_statuses_arr['wc-incompletepayment'] = __( 'Incomplete Payment', 'copperx' );
			$new_statuses_arr['wc-blockchainpending'] = __( 'Blockchain Pending', 'copperx' );
		}
	}

	return $new_statuses_arr;
}



/**
 * Add order Copperx meta after General and before Billing
 *
 * @param WC_Order $order WC order instance
 *
 * @see: https://rudrastyh.com/woocommerce/customize-order-details.html
 */
function copperx_order_meta_general( $order )
{
    if ($order->get_payment_method() == 'copperx') {
        ?>
        <br/>
        <h4>Copperx Payment Details</h4>
        <div class="">
            <p>Copperx Reference Payment ID # <a href="<?php echo esc_html($order->get_meta('_copperx_checkout_url')); ?>" target="_blank"><?php echo esc_html($order->get_meta('_copperx_checkout_id')); ?></a></p>
        </div>

        <?php
    }
}
