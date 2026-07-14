<?php


namespace WallidCommerceGateway;


class CheckoutIcon
{
    /**
     * Enqueue checkout icon styles on storefront pages.
     */
    public static function enqueue_styles()
    {
        if (!function_exists('is_checkout') || (!is_checkout() && !is_cart())) {
            return;
        }

        $handle = 'wallid-checkout-icon';
        $style_url = plugin_dir_url(dirname(__FILE__)) . 'assets/css/wallid-checkout.css';

        wp_register_style($handle, $style_url, [], '1.1.12');
        wp_enqueue_style($handle);
    }

    public static function get($id)
    {
        $icon_html = '';
        $providers = array('wallid-payment-logo');
        foreach ($providers as $provider) {
            $url = \WC_HTTPS::force_https_url(plugin_dir_url(dirname(__FILE__, 1)) . 'assets/' . $provider . '.svg');
            $icon_html .= '<img width="26" src="' . esc_attr($url) . '" alt="' . esc_attr($provider) . '" />';
        }

        return apply_filters('woocommerce_gateway_icon', $icon_html, $id);
    }
}
