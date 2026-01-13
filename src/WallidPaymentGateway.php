<?php

namespace WallidCommerceGateway;

use WC_Payment_Gateway;

final class WallidPaymentGateway extends WC_Payment_Gateway
{
    private $terminal_id;
    private $terminal_secret;

    public function __construct()
    {
        error_log('Wallid Payment Gateway: Constructor called');
        
        $this->id = 'wallid_payment';
        $this->method_title = 'Wallid';

        $this->method_description = "Fast instant bank to bank payments";  // to backend
        $this->order_button_text = 'Proceed to pay';

        $this->title = $this->get_option('title') ?: 'Pay by bank';
        $this->description = $this->get_option('description') ?: 'Pay instantly via online bank transfer - Supports most of the U.K banks';
        
        // Ensure title and description are not empty (required for display)
        if (empty($this->title)) {
            $this->title = 'Pay by bank';
        }
        if (empty($this->description)) {
            $this->description = 'Pay instantly via online bank transfer';
        }

        $this->has_fields = false;

        // Support products
        $this->supports = array(
            'products'
        );

        $this->countries = []; // Empty array = available for all countries

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled = $this->get_option('enabled');
        $this->terminal_id = $this->get_option('terminal_id');
        $this->terminal_secret = $this->get_option('terminal_secret');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action('woocommerce_api_wallid', array($this, 'webhook'));
        
        error_log('Wallid Payment Gateway: Constructor completed. ID: ' . $this->id . ', Enabled: ' . $this->enabled);
    }

    public function init_form_fields()
    {
        $this->form_fields = AdminPortalOptions::get();
    }

    function admin_options()
    {
        AdminPortalUI::get($this->generate_settings_html([], false));
    }

    public function process_admin_options()
    {
        parent::process_admin_options();
        
        // Get the updated values after saving
        $terminal_secret = $this->get_option('terminal_secret');
        $terminal_id = $this->get_option('terminal_id');
        
        // Clear cache when settings are updated
        wp_cache_delete('wallid_payment_method_data', 'wallid_gateway');
        
        return AdminPortalOptions::validate($terminal_secret, $terminal_id);
    }

    public function process_payment($order_id)
    {
        return PaymentProcess::process($order_id, $this->terminal_id, $this->terminal_secret);
    }

    public function get_icon()
    {
        return CheckoutIcon::get($this->id);
    }

    public function is_available()
    {
        // Check if gateway is enabled
        $enabled = $this->get_option('enabled');
        if ($enabled !== 'yes') {
            return false;
        }

        // Check if required credentials are set (read fresh from options)
        $terminal_id = $this->get_option('terminal_id');
        $terminal_secret = $this->get_option('terminal_secret');
        
        if (empty($terminal_id) || empty($terminal_secret)) {
            return false;
        }

        // Check country restriction if set
        if (!empty($this->countries)) {
            // Check customer's billing country if available (during checkout)
            $customer_country = '';
            
            // Try to get customer country from various sources
            if (function_exists('WC') && WC()->customer) {
                $customer_country = WC()->customer->get_billing_country();
            }
            
            // If no customer country, check base location
            if (empty($customer_country)) {
                $base_location = wc_get_base_location();
                $customer_country = isset($base_location['country']) ? $base_location['country'] : '';
            }
            
            // Convert UK to GB if needed (WooCommerce uses GB)
            if ($customer_country === 'UK') {
                $customer_country = 'GB';
            }
            
            if (!empty($customer_country) && !in_array($customer_country, $this->countries)) {
                return false;
            }
        }

        return parent::is_available();
    }

    public function webhook()
    {
        PaymentNotification::process($this->terminal_id, $this->terminal_secret);
    }
}
