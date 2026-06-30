<?php
/**
 * Plugin Name:       Wallid Pay By Bank 
 * Plugin URI:        https://wallid.co
 * Description:       Wallid enables merchants to accept account-to-account payments in WooCommerce using Open Banking.
 * Version:           1.1.9
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Wallid
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
if (!defined('ABSPATH')) {
    exit;
}

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
    require_once $plugin_path . 'src/WallidLogger.php';
    require_once $plugin_path . 'src/WallidApiClient.php';
    require_once $plugin_path . 'src/AdminPortalOptions.php';
    require_once $plugin_path . 'src/AdminPortalUI.php';
    require_once $plugin_path . 'src/CheckoutIcon.php';
    require_once $plugin_path . 'src/PaymentProcess.php';
    require_once $plugin_path . 'src/PaymentNotification.php';
    require_once $plugin_path . 'src/WallidPaymentGateway.php';

    // Register webhook receiver at bootstrap level so callback handling is
    // always available, regardless of gateway instantiation timing.
    add_action('woocommerce_api_wallid', 'wallid_handle_wc_api_webhook');
    function wallid_handle_wc_api_webhook()
    {
        $settings = get_option('woocommerce_wallid_payment_settings', []);
        $terminal_id = isset($settings['terminal_id']) ? $settings['terminal_id'] : '';
        $terminal_secret = isset($settings['terminal_secret']) ? $settings['terminal_secret'] : '';
        \WallidCommerceGateway\PaymentNotification::process($terminal_id, $terminal_secret);
    }
    
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
            }
        }
    }
}


add_action('plugins_loaded', 'woocommerce_gateway_wallid_init');
