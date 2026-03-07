<?php


namespace WallidCommerceGateway;

class PaymentNotification
{
    /**
     * Send a JSON response and exit.
     *
     * @param int   $http_code HTTP status code
     * @param array $payload   Response payload
     */
    private static function sendJsonResponse($http_code, array $payload)
    {
        http_response_code($http_code);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    /**
     * Send JSON error response and exit.
     *
     * @param int    $http_code HTTP status code
     * @param string $error     Error type/code
     * @param string $message   Human-readable message
     */
    private static function sendJsonError($http_code, $error, $message)
    {
        self::sendJsonResponse($http_code, [
            'error'   => $error,
            'message' => $message,
        ]);
    }

    /**
     * Resolve a webhook order reference to a WooCommerce order.
     *
     * Supports normal numeric WooCommerce order IDs and common custom
     * order number meta keys used by order-number plugins.
     *
     * @param mixed $order_reference Order ID or custom order number
     * @return \WC_Order|false
     */
    private static function resolveOrder($order_reference)
    {
        if (!is_scalar($order_reference)) {
            return false;
        }

        $order_reference = trim((string) $order_reference);

        if ($order_reference === '') {
            return false;
        }

        // Keep the normal WooCommerce numeric-ID path intact.
        if (ctype_digit($order_reference)) {
            $order = wc_get_order((int) $order_reference);
            if ($order instanceof \WC_Order) {
                return $order;
            }
        }

        $meta_keys = [
            '_alg_wc_full_custom_order_number',
            '_order_number_formatted',
            '_order_number',
            '_wcj_order_number',
        ];

        foreach ($meta_keys as $meta_key) {
            $orders = wc_get_orders([
                'limit'      => 1,
                'return'     => 'objects',
                'type'       => 'shop_order',
                'status'     => array_keys(wc_get_order_statuses()),
                'meta_key'   => $meta_key,
                'meta_value' => $order_reference,
            ]);

            if (!empty($orders) && $orders[0] instanceof \WC_Order) {
                return $orders[0];
            }
        }

        return false;
    }

    public static function process($terminal_id, $terminal_secret)
    {
        global $woocommerce;

        // Get raw request body for signature verification
        $raw_body = file_get_contents('php://input');

        // Validate webhook signature
        if (!self::validateWebhookSignature($raw_body, $terminal_secret)) {
            error_log("Wallid webhook: Invalid signature");
            self::sendJsonError(401, 'Webhook validation failed', 'Invalid signature');
        }

        $data = json_decode($raw_body, true);

        if (!isset($data['status'])) {
            error_log("Wallid webhook: Status not in data");
            self::sendJsonError(400, 'Bad request', 'Missing required field: status');
        }
        if (!isset($data['order_id'])) {
            error_log("Wallid webhook: Order ID not in data");
            self::sendJsonError(400, 'Bad request', 'Missing required field: order_id');
        }

        $order_number = $data['order_id'];
        $status = $data['status'];
        $amount = $data['amount'];
        $order = self::resolveOrder($order_number);

        if (!$order || !($order instanceof \WC_Order)) {
            error_log("Wallid webhook: Order not found for order reference " . $order_number);
            self::sendJsonError(404, 'Order not found', 'No WooCommerce order found for the given order_id');
        }

        $orderId = $order->get_id();

        $order->add_order_note("WALLID: Order found, processing the webhook with status " . $status, 0);

        if ($status == 'sent') {
            error_log("Wallid webhook: Status is still sent, no action taken");
            self::sendJsonResponse(200, [
                'message' => 'Status unchanged',
                'status'  => 'sent',
            ]);
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

        self::sendJsonResponse(200, [
            'message' => 'Webhook processed',
            'status' => $status,
            'order_id' => $order_number,
        ]);
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
