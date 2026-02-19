<?php

namespace WallidCommerceGateway;

class WallidApiClient
{
    private $terminal_id;
    private $terminal_secret;
    private $api_base_url;

    public function __construct($terminal_id, $terminal_secret, $api_base_url = 'https://pay.wallid.co')
    {
        $this->terminal_id = $terminal_id;
        $this->terminal_secret = $terminal_secret;
        $this->api_base_url = rtrim($api_base_url, '/');
    }

    /**
     * Create and process a payment
     *
     * @param float $total Payment total amount
     * @param string $orderId Order ID/reference number
     * @param string $currency Currency code (e.g., 'GBP', 'USD')
     * @param string $checkoutUrl Checkout URL
     * @param string $successUrl Success redirect URL
     * @param string $payRedirectUrl Base URL for payment redirect (e.g., 'https://pay.wallid.co')
     * @return array|false Returns array with 'url' and 'paymentId' on success, false on failure
     */
    public function createPayment($total, $orderId, $currency, $checkoutUrl, $successUrl = null, $payRedirectUrl = null)
    {
        // Validate shopId/terminal_id is set
        if (empty($this->terminal_id)) {
            error_log('Wallid API Error: shopId (terminal_id) is empty');
            return false;
        }
        
        $payload = [
            'currency' => $currency,
            'total' => $total,
            'orderId' => $orderId,
            'shopId' => $this->terminal_id, // Using terminal_id as shopId
            'checkoutUrl' => $checkoutUrl,
            'successUrl' => $successUrl ? $successUrl : $checkoutUrl,
            'paymentType' => 'WOOCOMMERCE',
        ];

        // Public endpoint - authentication via shopId in payload
        $response = $this->makeRequest('POST', '/api/payment/public/v1/pay/create', $payload, true);

        if ($response === false) {
            return false;
        }

        // Handle response: on success returns only paymentId (string), on failure returns error
        $paymentId = null;
        
        // Check for error response format: { "errors": [...] }
        if (is_array($response) && isset($response['errors']) && is_array($response['errors'])) {
            $errorDetails = [];
            foreach ($response['errors'] as $error) {
                $errorDetails[] = isset($error['detail']) ? $error['detail'] : (isset($error['title']) ? $error['title'] : 'Unknown error');
            }
            $errorMsg = implode(', ', $errorDetails);
            error_log('Wallid API Error: ' . $errorMsg);
            return false;
        }
        
        // Check if response is a string (paymentId directly)
        if (is_string($response)) {
            $paymentId = $response;
        }
        // Check if response is an array/object with paymentId field
        elseif (is_array($response) && isset($response['paymentId'])) {
            $paymentId = $response['paymentId'];
        }
        // If response has error fields, it's a failure
        elseif (is_array($response) && (isset($response['error']) || isset($response['message']) || isset($response['code']))) {
            $errorMsg = isset($response['error']) ? $response['error'] : (isset($response['message']) ? $response['message'] : 'Unknown error');
            error_log('Wallid API Error: ' . $errorMsg);
            return false;
        }
        
        if (!$paymentId || !is_string($paymentId)) {
            error_log('Wallid API Error: paymentId not found or invalid in response');
            return false;
        }

        // Build redirect URL with base64 encoded payment data
        $finalUrl = $this->buildPaymentRedirectUrl($payRedirectUrl ?: $this->api_base_url, $paymentId);

        return [
            'url' => $finalUrl,
            'paymentId' => $paymentId
        ];
    }

    /**
     * Build payment redirect URL with base64 encoded payment data
     *
     * @param string $baseUrl Base redirect URL
     * @param string $paymentId Payment ID
     * @return string Final redirect URL with encoded data parameter
     */
    private function buildPaymentRedirectUrl($baseUrl, $paymentId)
    {
        // Parse the base URL
        $parsedUrl = parse_url($baseUrl);
        
        if ($parsedUrl === false) {
            error_log('Wallid API Error: Invalid base URL for payment redirect');
            return $baseUrl; // Return base URL as fallback
        }

        // Create base64 encoded JSON with paymentId
        $paymentData = json_encode(['paymentId' => $paymentId]);
        $base64PaymentData = base64_encode($paymentData);

        // Build the URL with query parameter
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : 'https';
        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

        $url = $scheme . '://' . $host . $port . $path;
        $url .= '?data=' . urlencode($base64PaymentData);

        return $url;
    }

    /**
     * Check payment status by payment ID
     *
     * @param string $paymentId Payment ID
     * @return array|false Returns payment data on success, false on failure
     */
    public function checkStatusByPaymentId($paymentId)
    {
        // Update endpoint based on actual API structure
        // Assuming endpoint format: /api/payment/public/v1/pay/{paymentId}/status
        $response = $this->makeRequest('GET', '/api/payment/public/v1/pay/' . urlencode($paymentId) . '/status', null, true);

        if ($response === false) {
            return false;
        }

        return [
            'data' => $response
        ];
    }

    /**
     * Make HTTP request to the API
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint
     * @param array|null $data Request payload for POST requests
     * @param bool $isPublic Whether this is a public endpoint (no auth headers needed)
     * @return array|false Decoded JSON response or false on failure
     */
    private function makeRequest($method, $endpoint, $data = null, $isPublic = false)
    {
        $url = $this->api_base_url . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $headers = ['Content-Type: application/json'];
        
        // Add authentication headers only for non-public endpoints
        if (!$isPublic) {
            $headers[] = 'X-Terminal-ID: ' . $this->terminal_id;
            $headers[] = 'X-Terminal-Secret: ' . $this->terminal_secret;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Set HTTP method
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        // SSL verification (you may want to make this configurable)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            error_log('Wallid API Error: ' . $curlError);
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            // Log only HTTP code and sanitized error message, not full response
            $errorMessage = 'HTTP ' . $httpCode;
            $decoded_response = json_decode($response, true);
            if (is_array($decoded_response) && isset($decoded_response['errors'])) {
                $errorDetails = [];
                foreach ($decoded_response['errors'] as $error) {
                    $errorDetails[] = isset($error['detail']) ? $error['detail'] : (isset($error['title']) ? $error['title'] : 'Unknown error');
                }
                $errorMessage .= ' - ' . implode(', ', $errorDetails);
            } elseif (is_array($decoded_response) && (isset($decoded_response['error']) || isset($decoded_response['message']))) {
                $errorMessage .= ' - ' . (isset($decoded_response['error']) ? $decoded_response['error'] : $decoded_response['message']);
            }
            error_log('Wallid API HTTP Error: ' . $errorMessage);
            return false;
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Wallid API JSON Error: ' . json_last_error_msg());
            return false;
        }

        return $decoded;
    }
}

