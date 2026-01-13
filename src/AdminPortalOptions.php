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
            'terminal_id' => array(
                'title' => 'Integration ID',
                'type' => 'text',
                'description' => 'Enter the terminal ID Here.',
                'default' => '',
                'desc_tip' => true,
            ),

            'terminal_secret' => array(
                'title' => 'Integration Secret',
                'type' => 'text',
                'description' => 'Enter the terminal Secret Here',
                'default' => '',
                'desc_tip' => true,
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'Pay by bank',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'text',
                'desc_tip' => true,
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => 'Pay instantly via online bank transfer - Supports most of the U.K banks',
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
