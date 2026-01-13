<?php

namespace WallidCommerceGateway;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Wallid Payment Gateway Blocks Integration
 */
final class WallidBlocksIntegration extends AbstractPaymentMethodType
{
    /**
     * The gateway instance.
     *
     * @var WallidPaymentGateway
     */
    private $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'wallid_payment';

    /**
     * Initializes the payment method type.
     */
    public function initialize()
    {
        $this->settings = get_option('woocommerce_wallid_payment_settings', []);
        $payment_gateways_class = WC()->payment_gateways();
        $payment_gateways = $payment_gateways_class->payment_gateways();
        $this->gateway = isset($payment_gateways['wallid_payment']) ? $payment_gateways['wallid_payment'] : null;
        
        // Clear cache if settings changed (checked via option update hook)
        // This is handled in WallidPaymentGateway::process_admin_options()
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active()
    {
        $is_active = $this->gateway && $this->gateway->is_available();
        error_log('Wallid Payment Gateway: is_active() = ' . ($is_active ? 'true' : 'false'));
        return $is_active;
    }

    /**
     * Returns an array of script handles to enqueue for this payment method in
     * the frontend context.
     *
     * @return string[]
     */
    public function get_payment_method_script_handles()
    {
        $script_handle = 'wallid-payment-method-blocks';
        
        // Register the script if not already registered
        if (!wp_script_is($script_handle, 'registered')) {
            // Get plugin directory URL
            $plugin_dir = dirname(dirname(__FILE__));
            $plugin_url = plugin_dir_url($plugin_dir . '/wallid-commerce-gateway.php');
            $script_path = 'assets/js/wallid-payment-method.js';
            $script_url = $plugin_url . $script_path;
            
            wp_register_script(
                $script_handle,
                $script_url,
                [
                    'wc-blocks-registry',
                    'wc-settings',
                    'wp-element',
                    'wp-html-entities',
                ],
                '1.0.1',
                true // Keep in footer where WooCommerce Blocks expects it
            );
            
            error_log('Wallid Payment Gateway: Script registered at ' . $script_url);
            
            // Note: Payment method data is automatically made available via getSetting('wallid_payment_data')
            // WooCommerce Blocks automatically appends '_data' to the payment method name
            // So for name='wallid_payment', data is available as getSetting('wallid_payment_data')
        }
        
        return [$script_handle];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     * Uses caching to improve performance.
     *
     * @return array
     */
    public function get_payment_method_data()
    {
        // Cache key
        $cache_key = 'wallid_payment_method_data';
        
        // Try to get from cache first
        $cached_data = wp_cache_get($cache_key, 'wallid_gateway');
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Generate data if not cached
        if (!$this->gateway) {
            $data = [
                'title' => 'Pay by bank',
                'description' => 'Pay instantly via online bank transfer - Supports most of the U.K banks',
                'icon' => '',
                'supports' => ['products'],
            ];
        } else {
            $data = [
                'title' => $this->gateway->get_option('title', 'Pay by bank'),
                'description' => $this->gateway->get_option('description', 'Pay instantly via online bank transfer - Supports most of the U.K banks'),
                'icon' => $this->gateway->get_icon(),
                'supports' => $this->gateway->supports,
            ];
        }
        
        // Cache for 1 hour (3600 seconds)
        // This data rarely changes, so caching improves performance significantly
        wp_cache_set($cache_key, $data, 'wallid_gateway', 3600);
        
        return $data;
    }
}
