<?php


namespace WallidCommerceGateway;


class AdminPortalOptions
{

    public static function get()
    {
        return array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable Wallid Gateway',
                'default' => 'yes'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This what title user sees during checkout.',
                'default' => 'Wallid: Pay by Bank',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'text',
                'desc_tip' => true,
                'description' => 'This what description user sees during checkout.',
                'default' => 'Pay securely with your bank app — no card needed',
            ),
            'terminal_id' => array(
                'title' => 'ID',
                'type' => 'text',
                'description' => 'Enter the ID here.',
                'default' => '',
                'desc_tip' => true,
            ),
            'terminal_secret' => array(
                'title' => 'Secret',
                'type' => 'text',
                'description' => 'Enter the Secret here.',
                'default' => '',
                'desc_tip' => true,
            ),
        );
    }

    public static function validate($terminal_secret, $terminal_id)
    {
        if (!$terminal_secret) {
            \WC_Admin_Settings::add_error('Invalid Terminal Secret Given');
            return false;
        }


        if (!$terminal_id) {
            \WC_Admin_Settings::add_error('Invalid Terminal ID Given');
            return false;
        }
        return true;
    }
}
