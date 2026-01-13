<?php


namespace WallidCommerceGateway;

class PaymentNotification
{

    public static function process($terminal_id, $terminal_secret)
    {
        global $woocommerce;

        error_log("Wallid webhook processing started");

        // Get raw request body for signature verification
        $raw_body = file_get_contents('php://input');
        
        // Validate webhook signature
        if (!self::validateWebhookSignature($raw_body, $terminal_secret)) {
            error_log("Wallid webhook: Invalid signature");
            http_response_code(401);
            die('Invalid signature');
        }

        $data = json_decode($raw_body, true);

        if (!isset($data['status'])) {
            error_log("Status not in data");
            die();
        }
        if (!isset($data['order_id'])) {
            error_log("Order ID not in data");
            die();
        }

        $order_number = $data['order_id'];
        $status = $data['status'];
        $amount = $data['amount'];

        $args    = array(
            'post_type'      => 'shop_order',
            'post_status'    => 'any',
            'meta_query'     => array(
                array(
                    'key'        => '_alg_wc_full_custom_order_number',
                    'value'      => $order_number,  //here you pass the Order Number
                    'compare'    => '=',
                )
            )
        );
        $query   = new \WP_Query( $args );
        if ( !empty( $query->posts ) ) {
            $orderId = $query->posts[ 0 ]->ID;
        } else {
            $args    = array(
                'post_type'      => 'shop_order',
                'post_status'    => 'any',
                'meta_query'     => array(
                    array(
                        'key'        => '_order_number',
                        'value'      => $order_number,  //here you pass the Order Number
                        'compare'    => '=',
                    )
                )
            );
            $query   = new \WP_Query( $args );
            if ( !empty( $query->posts ) ) {
                $orderId = $query->posts[ 0 ]->ID;
            } else {
                $args    = array(
                    'post_type'      => 'shop_order',
                    'post_status'    => 'any',
                    'meta_query'     => array(
                        array(
                            'key'        => '_wcj_order_number',
                            'value'      => $order_number,  //here you pass the Order Number
                            'compare'    => '=',
                        )
                    )
                );
                $query   = new \WP_Query( $args );
                if ( !empty( $query->posts ) ) {
                    $orderId = $query->posts[ 0 ]->ID;
                } else {
                    $orderId = $order_number;
                }
            }
        }

        if (!isset($orderId)) {
            error_log( "Order ID not found" );
            die();
        }

        $order = wc_get_order($orderId);

        if (!isset($order)) {
            error_log( "Order not found" );
            die();
        }

        $order->add_order_note("WALLID: Order found, processing the webhook with status " . $status, 0);

        if ($status == 'sent') {
            error_log("Status is still sent");
            die();
        }

        if ($status == 'paid') {
            $order->add_order_note("WooCommerce Default Order ID {$orderId}", 0);
            $order->add_order_note("WooCommerce Order Number (wallid Reference): {$order_number}", 0);
            $order->add_order_note("Wallid Net Amount £{$amount}", 0);
            $woocommerce->cart->empty_cart();

            if (isset($data['reference'])) {
                $reference = $data['reference'];
                $order->payment_complete( $reference );
                $order->add_order_note("Wallid Reference {$reference}", 0);
            }
        }
        if ($status == 'failed') {
            $order->add_order_note("WooCommerce Default Order ID {$orderId}", 0);
            $order->add_order_note("WooCommerce Order Number (Wallid Reference): {$order_number}", 0);
            $order->add_order_note("The payment has been failed", 0);

            if (isset($data['reference'])) {
                $reference = $data['reference'];
                $order->set_transaction_id($reference);
                $order->add_order_note("Wallid Reference {$reference}", 0);
            }

            $order->cancel_order();
        }
        exit();
    }

    /**
     * Validate webhook signature using HMAC SHA256
     * 
     * @param string $raw_body Raw request body
     * @param string $terminal_secret Terminal secret for signature verification
     * @return bool True if signature is valid, false otherwise
     */
    private static function validateWebhookSignature($raw_body, $terminal_secret)
    {
        // Get signature from header
        $signature_header = isset($_SERVER['HTTP_X_WALLID_SIGNATURE']) 
            ? $_SERVER['HTTP_X_WALLID_SIGNATURE'] 
            : (isset($_SERVER['HTTP_X_WEBHOOK_SIGNATURE']) ? $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] : '');
        
        if (empty($signature_header)) {
            error_log("Wallid webhook: Signature header missing");
            return false;
        }

        if (empty($terminal_secret)) {
            error_log("Wallid webhook: Terminal secret not configured");
            return false;
        }

        // Calculate expected signature using HMAC SHA256
        $expected_signature = hash_hmac('sha256', $raw_body, $terminal_secret);
        
        // Use constant-time comparison to prevent timing attacks
        if (!hash_equals($expected_signature, $signature_header)) {
            error_log("Wallid webhook: Signature mismatch");
            return false;
        }

        return true;
    }

}
