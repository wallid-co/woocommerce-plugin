<?php


namespace WallidCommerceGateway;

class PaymentProcess
{

    public static function process($order_id, $terminal_id, $terminal_secret)
    {

//        ini_set('display_errors', 1);
//        ini_set('display_startup_errors', 1);
//        error_reporting(E_ALL);

        $order = new \WC_Order($order_id);

        if ($order->get_total() < 0.50) {
            return array(
                'result' => 'failure',
                'messages' => 'Total amount should be greater than 0.50.'
            );
        }

        $order_number = $order->get_order_number(); // To be used with the Custom Order Numbers For Woocommerce Plugin
        
        // Get the order received (thank you) page URL for successful payments
        $successUrl = $order->get_checkout_order_received_url();
        
        // Get checkout URL for cancelled/abandoned payments
        $checkoutUrl = wc_get_checkout_url();
        
        $currency = $order->get_currency() ?: 'GBP'; // Default to GBP if not set

        // Initialize API client
        $apiClient = new WallidApiClient($terminal_id, $terminal_secret);

        // Create payment via API
        // Using API base URL as payRedirectUrl (can be configured if needed)
        $payRedirectUrl = 'https://pay.stg.wlld.dev'; // Base URL for payment redirect
        
        $paymentResult = $apiClient->createPayment(
            $order->get_total(),
            $order_number,
            $currency,
            $checkoutUrl, // URL to redirect if payment is cancelled/abandoned
            $successUrl,  // URL to redirect after successful payment (order received page)
            $payRedirectUrl
        );

        if ($paymentResult === false || !isset($paymentResult['url']) || !isset($paymentResult['paymentId'])) {
            return array(
                'result' => 'failure',
                'messages' => 'Something went wrong. Please contact support.'
            );
        }

        $url = $paymentResult['url'];
        $paymentId = $paymentResult['paymentId'];

        $order->update_status('awaiting_payment', 'Awaiting payment');

        $order->add_meta_data( '_wallid_payment_id', $paymentId );
        $order->add_meta_data( '_wallid_payment_url', $url );
        $order->save_meta_data();

        return array(
            'result' => 'success',
            'redirect' => $url
        );
    }

}
