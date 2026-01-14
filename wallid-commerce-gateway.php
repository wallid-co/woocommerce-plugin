<?php
/**
 * Plugin Name:       Wallid Pay By Bank 
 * Plugin URI:        https://wallid.co
 * Description:       Wallid enables merchants to accept account-to-account payments in WooCommerce using Open Banking.
 * Version:           1.1.4
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Wallid
 * Author URI:        https://wallid.co
 * Text Domain:       wallid
 * Domain Path:       /languages
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * 
 * 
 * Wallid Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * 
 * Wallid Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Wallid Plugin. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
 */
function wallid_woocommerce_stripe_missing_wc_notice()
{
    echo '<div class="error"><p><strong>Wallid requires WooCommerce to be installed and active. You can download <a href="https://woocommerce.com/" target="_blank">WooCommerce</a> from here.</strong></p></div>';
}


function woocommerce_gateway_wallid_init()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wallid_woocommerce_stripe_missing_wc_notice');
        return;
    }

    // Load required classes only after WooCommerce is available
    $plugin_path = plugin_dir_path(__FILE__);
    require_once $plugin_path . 'src/WallidApiClient.php';
    require_once $plugin_path . 'src/AdminPortalOptions.php';
    require_once $plugin_path . 'src/AdminPortalUI.php';
    require_once $plugin_path . 'src/CheckoutIcon.php';
    require_once $plugin_path . 'src/PaymentProcess.php';
    require_once $plugin_path . 'src/PaymentNotification.php';
    require_once $plugin_path . 'src/WallidPaymentGateway.php';
    
    // Load Blocks integration if WooCommerce Blocks is active
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        require_once $plugin_path . 'src/WallidBlocksIntegration.php';
    }

    add_filter('woocommerce_payment_gateways', 'addWallidPaymentGateway', 10, 1);
    function addWallidPaymentGateway($gateways)
    {
        // Check if class exists before adding
        if (class_exists('WallidCommerceGateway\\WallidPaymentGateway')) {
            $gateways[] = 'WallidCommerceGateway\\WallidPaymentGateway';
            error_log('Wallid Payment Gateway: Successfully registered. Total gateways: ' . count($gateways));
        } else {
            error_log('Wallid Payment Gateway: ERROR - Class not found!');
        }
        return $gateways;
    }
    
    // Register Blocks integration for WooCommerce Blocks
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        add_action('woocommerce_blocks_payment_method_type_registration', 'wallid_register_blocks_integration');
        function wallid_register_blocks_integration($payment_method_registry)
        {
            if (class_exists('WallidCommerceGateway\\WallidBlocksIntegration')) {
                $payment_method_registry->register(new \WallidCommerceGateway\WallidBlocksIntegration());
                error_log('Wallid Payment Gateway: Blocks integration registered');
            }
        }
    }
    
    // Ensure gateway is available in checkout
    add_filter('woocommerce_available_payment_gateways', 'ensureWallidGatewayAvailable', 10, 1);
    function ensureWallidGatewayAvailable($available_gateways)
    {
        // Force add the gateway if it's not there but should be
        if (!isset($available_gateways['wallid_payment'])) {
            $gateway = new \WallidCommerceGateway\WallidPaymentGateway();
            if ($gateway->is_available()) {
                $available_gateways['wallid_payment'] = $gateway;
                error_log('Wallid Payment Gateway: Force added to available gateways');
            } else {
                error_log('Wallid Payment Gateway: NOT available, not adding');
            }
        } else {
            error_log('Wallid Payment Gateway: Already in available gateways');
        }
        
        // Debug: Log all available gateways
        error_log('Wallid Payment Gateway: Available gateways on checkout: ' . implode(', ', array_keys($available_gateways)));
        
        return $available_gateways;
    }
    
    // Add debug info to checkout page (temporary)
    add_action('wp_footer', function() {
        if (is_checkout()) {
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            echo '<!-- Wallid Debug: Available gateways: ' . implode(', ', array_keys($available_gateways)) . ' -->';
            if (isset($available_gateways['wallid_payment'])) {
                echo '<!-- Wallid Debug: wallid_payment IS available -->';
            } else {
                echo '<!-- Wallid Debug: wallid_payment NOT in available gateways -->';
            }
        }
    });
}


add_action('plugins_loaded', 'woocommerce_gateway_wallid_init');
