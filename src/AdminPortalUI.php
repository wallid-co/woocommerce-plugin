<?php


namespace WallidCommerceGateway;


class AdminPortalUI
{
    public static function get($settings)
    {
        ?>
        <h2>Wallid Payment Gateway</h2>
        <table class="form-table">
            <?php echo $settings; ?>
        </table>

        <h4>Payment Notification URL</h4>
        <pre><?php echo home_url('/wc-api/wallid', 'https'); ?></pre>

        <h4>Redirect URL</h4>
        <pre><?php echo wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() ); ?></pre>
        <?php
    }
}
