<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Mpesa_Blocks extends AbstractPaymentMethodType
{
    protected $name = 'wc-mpesa-payment-gateway';

    public function initialize()
    {
        $this->settings = get_option('woocommerce_wc-mpesa-payment-gateway_settings', []);
    }

    public function is_active()
    {
        return filter_var($this->get_setting('enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'wc-mpesa-blocks-integration',
            plugin_dir_url(__FILE__) . 'checkout_block.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wc-blocks-data-store',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
                'wp-data',
            ],
            null,
            true
        );

        wp_register_style(
            'wc-mpesa-blocks-integration',
            plugin_dir_url(__FILE__) . 'checkout_block.css',
            [],
            null
        );
        wp_enqueue_style('wc-mpesa-blocks-integration');

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('wc-mpesa-blocks-integration', 'wc-mpesa-payment-gateway');
        }

        return ['wc-mpesa-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->get_setting('title', 'Mpesa for WooCommerce'),
            'description' => $this->get_setting('description', 'Pay via mpesa'),
            'icon' => plugins_url('../assets/img/m-pesa-logo.png', __FILE__),
            'supports' => $this->get_supported_features(),
            'testMode' => 'yes' === $this->get_setting('test', 'yes'),
            'placeholder' => __('ex: 84 123 XXXX', 'wc-mpesa-payment-gateway'),
            'currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '',
            'waitingSeconds' => 60,
        ];
    }
}
