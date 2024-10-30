<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Checkout_Block_Gateway_Copperx class.
 *
 * @extends AbstractPaymentMethodType
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
final class WC_Checkout_Block_Gateway_Copperx extends AbstractPaymentMethodType
{
    private $gateway;

    /**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
    protected $name = 'copperx';

    /**
	 * Initializes the payment method type.
	 */
    public function initialize()
    {
        $this->settings = get_option('woocommerce_copperx_settings', []);
        $this->gateway = new WC_Gateway_Copperx();
    }

    /**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
        return $this->gateway->is_available();
	}


    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'wc-copperx-blocks-integration',
            plugin_dir_url(__FILE__) . 'block/copperx_checkout_block.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );

        if (function_exists('wp_set_script_translations'))
        {
            wp_set_script_translations('wc-copperx-blocks-integration');
        }

        return ['wc-copperx-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->settings['name'],
            'description' => $this->settings['description'],
            'icons_html' =>  $this->gateway->get_icon(),
        ];
    }

}
