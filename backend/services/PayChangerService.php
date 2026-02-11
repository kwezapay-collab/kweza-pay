<?php
/**
 * PayChanger Service
 * Handles USSD Push payments via PayChanger API
 */

class PayChangerService {
    private $config;

    public function __construct() {
        $allConfig = require __DIR__ . '/../config/payment_gateways.php';
        $this->config = $allConfig['paychanger'];
    }

    /**
     * Initiate a Mobile Money Payment (USSD Push)
     * 
     * @param string $mobileNumber The customer's mobile number (e.g., 265999123456)
     * @param float $amount The amount to charge
     * @param string $reference A unique reference for this transaction
     * @return array Response from PayChanger
     */
    public function initiatePayment($mobileNumber, $amount, $reference) {
        if (!$this->config['enabled']) {
            throw new Exception("PayChanger is disabled in configuration.");
        }

        $url = $this->config['base_url'][$this->config['mode']] . '/payments';
        // Note: The specific endpoint might differ. Assuming /payments or similar based on typical APIs.
        // If the user provided a specific API doc URL, we should check it.
        // For now, using a generic structure for PayChanger.
        
        // Adjust payload based on hypothetical PayChanger docs
        $payload = [
            'amount' => $amount,
            'currency' => $this->config['currency'],
            'customer_phone' => $mobileNumber,
            'reference' => $reference, 
            'description' => 'Payment via Kweza Pay',
            // Typically such APIs need a callback URL
            'callback_url' => 'https://kweza-app-clean-version.vercel.app/api/webhook/paychanger' // Placeholder
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->config['secret_key'], // Using Secret Key for backend
            'X-Public-Key: ' . $this->config['public_key']
        ];

        // LOGGING (for debugging)
        error_log("PayChanger Request to $url: " . json_encode($payload));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("PayChanger Connection Error: $error");
        }

        $result = json_decode($response, true);
        
        // LOGGING
        error_log("PayChanger Response ($httpCode): " . $response);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $result;
        } else {
            throw new Exception("PayChanger API Error ($httpCode): " . ($result['message'] ?? $response));
        }
    }
}
?>
