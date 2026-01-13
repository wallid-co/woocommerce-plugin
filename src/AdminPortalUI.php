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
        <?php
    }
}
