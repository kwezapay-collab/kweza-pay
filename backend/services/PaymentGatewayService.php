<?php
/**
 * Payment Gateway Service
 * Handles real money transactions through Airtel Money, TNM Mpamba, and Paychannel
 */

class PaymentGatewayService {
    private $config;
    private $db;

    public function __construct($pdo) {
        $this->config = require __DIR__ . '/../config/payment_gateways.php';
        $this->db = $pdo;
    }

    /**
     * Process withdrawal to mobile money or bank
     */
    public function processWithdrawal($userId, $provider, $accountNumber, $amount, $pin) {
        // Verify user and PIN
        $stmt = $this->db->prepare("SELECT wallet_balance, pin_hash FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($pin, $user['pin_hash'])) {
            throw new Exception('Invalid PIN');
        }

        if ($user['wallet_balance'] < $amount) {
            throw new Exception('Insufficient funds');
        }

        // Check if we're in virtual mode or real API mode
        if ($this->config['virtual_mode']['enabled']) {
            return $this->processVirtualWithdrawal($userId, $provider, $accountNumber, $amount);
        }

        // Route to appropriate payment provider
        switch(strtolower($provider)) {
            case 'airtel money':
                return $this->withdrawAirtelMoney($userId, $accountNumber, $amount);
            case 'tnm mpamba':
                return $this->withdrawTNMMpamba($userId, $accountNumber, $amount);
            case 'bank transfer':
                return $this->withdrawPaychannel($userId, $accountNumber, $amount);
            default:
                throw new Exception('Invalid payment provider');
        }
    }

    /**
     * Virtual withdrawal (demo mode)
     */
    private function processVirtualWithdrawal($userId, $provider, $accountNumber, $amount) {
        // Simulate API delay
        if ($this->config['virtual_mode']['simulate_delay'] > 0) {
            sleep($this->config['virtual_mode']['simulate_delay']);
        }

        $this->db->beginTransaction();

        // Deduct from wallet
        $stmt = $this->db->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE user_id = ?");
        $stmt->execute([$amount, $userId]);

        // Record transaction
        $ref = 'WITHDRAW-' . strtoupper(substr(uniqid(), -8));
        $stmt = $this->db->prepare("
            INSERT INTO transactions (txn_type, sender_id, amount, reference_code, description)
            VALUES ('WITHDRAWAL', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $amount,
            $ref,
            "Withdrawal to {$provider} - {$accountNumber} (VIRTUAL MODE)"
        ]);

        $this->db->commit();

        return [
            'success' => true,
            'reference' => $ref,
            'mode' => 'virtual',
            'message' => 'Virtual withdrawal successful. In production, real money will be transferred.'
        ];
    }

    /**
     * Airtel Money Withdrawal (Real API)
     */
    private function withdrawAirtelMoney($userId, $phoneNumber, $amount) {
        if (!$this->config['airtel_money']['enabled']) {
            throw new Exception('Airtel Money API not configured');
        }

        $config = $this->config['airtel_money'];
        $baseUrl = $config['base_url'][$config['mode']];

        // Step 1: Get OAuth token
        $tokenUrl = $baseUrl . '/auth/oauth2/token';
        $tokenData = [
            'client_id' => $config['api_key'],
            'client_secret' => $config['api_secret'],
            'grant_type' => 'client_credentials'
        ];

        $token = $this->makeApiRequest($tokenUrl, 'POST', $tokenData);

        // Step 2: Initiate disbursement
        $disbursementUrl = $baseUrl . '/standard/v1/disbursements/';
        $disbursementData = [
            'payee' => [
                'msisdn' => $phoneNumber
            ],
            'reference' => 'KWEZA-' . time(),
            'pin' => $config['merchant_id'],
            'transaction' => [
                'amount' => $amount,
                'type' => 'B2C',
                'id' => uniqid()
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $token['access_token'],
            'Content-Type: application/json',
            'X-Country: ' . $config['country'],
            'X-Currency: ' . $config['currency']
        ];

        $result = $this->makeApiRequest($disbursementUrl, 'POST', $disbursementData, $headers);

        // Record in database
        $this->recordRealWithdrawal($userId, $amount, $result, 'Airtel Money', $phoneNumber);

        return [
            'success' => true,
            'reference' => $result['data']['transaction']['id'],
            'mode' => 'real',
            'provider' => 'Airtel Money'
        ];
    }

    /**
     * TNM Mpamba Withdrawal (Real API)
     */
    private function withdrawTNMMpamba($userId, $phoneNumber, $amount) {
        if (!$this->config['tnm_mpamba']['enabled']) {
            throw new Exception('TNM Mpamba API not configured');
        }

        $config = $this->config['tnm_mpamba'];
        $baseUrl = $config['base_url'][$config['mode']];

        // TNM Mpamba API call structure (adjust based on actual API documentation)
        $url = $baseUrl . '/payment/withdraw';
        $data = [
            'merchant_code' => $config['merchant_code'],
            'phone_number' => $phoneNumber,
            'amount' => $amount,
            'currency' => $config['currency'],
            'reference' => 'KWEZA-' . time()
        ];

        $headers = [
            'Authorization: Bearer ' . $config['api_key'],
            'Content-Type: application/json'
        ];

        $result = $this->makeApiRequest($url, 'POST', $data, $headers);

        $this->recordRealWithdrawal($userId, $amount, $result, 'TNM Mpamba', $phoneNumber);

        return [
            'success' => true,
            'reference' => $result['transaction_id'],
            'mode' => 'real',
            'provider' => 'TNM Mpamba'
        ];
    }

    /**
     * Paychannel Bank Transfer (Real API)
     */
    private function withdrawPaychannel($userId, $accountNumber, $amount) {
        if (!$this->config['paychannel']['enabled']) {
            throw new Exception('Paychannel API not configured');
        }

        $config = $this->config['paychannel'];
        $baseUrl = $config['base_url'][$config['mode']];

        // Paychannel API structure
        $url = $baseUrl . '/disbursement';
        $data = [
            'merchant_id' => $config['merchant_id'],
            'account_number' => $accountNumber,
            'amount' => $amount,
            'currency' => $config['currency'],
            'reference' => 'KWEZA-' . time()
        ];

        $headers = [
            'API-Key: ' . $config['api_key'],
            'Content-Type: application/json'
        ];

        $result = $this->makeApiRequest($url, 'POST', $data, $headers);

        $this->recordRealWithdrawal($userId, $amount, $result, 'Bank Transfer', $accountNumber);

        return [
            'success' => true,
            'reference' => $result['reference_number'],
            'mode' => 'real',
            'provider' => 'Paychannel'
        ];
    }

    /**
     * Make API request with cURL
     */
    private function makeApiRequest($url, $method = 'GET', $data = null, $headers = []) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('API request failed with code: ' . $httpCode);
        }

        return json_decode($response, true);
    }

    /**
     * Record real withdrawal in database
     */
    private function recordRealWithdrawal($userId, $amount, $apiResponse, $provider, $destination) {
        $this->db->beginTransaction();

        // Deduct from wallet
        $stmt = $this->db->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE user_id = ?");
        $stmt->execute([$amount, $userId]);

        // Record transaction
        $ref = $apiResponse['reference_number'] ?? $apiResponse['transaction_id'] ?? uniqid();
        $stmt = $this->db->prepare("
            INSERT INTO transactions (txn_type, sender_id, amount, reference_code, description)
            VALUES ('WITHDRAWAL', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $amount,
            $ref,
            "Withdrawal to {$provider} - {$destination} (REAL MONEY)"
        ]);

        $this->db->commit();
    }
}
?>
