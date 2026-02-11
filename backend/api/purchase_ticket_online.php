<?php
// backend/api/purchase_ticket_online.php
require_once 'session.php';
require_once 'db.php';
// require_once 'PayChanguService.php'; // Not strictly needed if we bypass, but leaving for structure
// Only include if not in simulation mode, or include anyway but don't use
if (file_exists('PayChanguService.php')) {
    require_once 'PayChanguService.php';
}
require_once 'TicketService.php';

// Ensure user is logged in
requireLogin();

header('Content-Type: application/json');

$user = getCurrentUser($pdo);
$input = json_decode(file_get_contents('php://input'), true);

$event_id = $input['event_id'] ?? 0;

if (!$event_id) {
    echo json_encode(['status' => 'error', 'message' => 'Event ID is required']);
    exit;
}

try {
    // Get event details to know the price
    $stmt = $pdo->prepare("SELECT ticket_price, event_name FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        throw new Exception("Event not found");
    }

    $amount = $event['ticket_price'];

    // Construct a unique reference
    $tx_ref = 'TKT-' . $event_id . '-' . $user['user_id'] . '-' . time();

    // URLs
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $callbackUrl = $protocol . "://" . $host . "/kweza-app/backend/api/paychangu_webhook.php";
    $returnUrl = $protocol . "://" . $host . "/kweza-app/index.php?page=my_tickets&status=processing"; 

    // SIMULATION MODE CHECK
    if (defined('SIMULATION_MODE') && SIMULATION_MODE) {
        $ticketService = new TicketService($pdo);
        // Simulate successful payment
        $sim_ref = 'SIM-' . $event_id . '-' . $user['user_id'] . '-' . time();
        $result = $ticketService->issueTicket($event_id, $user['user_id'], $amount, 'PAYCHANGU_SIM', $sim_ref);
        
        // Return success with the return URL as checkout_url, so frontend redirects effectively to "success"
        echo json_encode(['success' => true, 'checkout_url' => $returnUrl]);
        exit;
    }

    // Real PayChangu Logic
    if (!class_exists('PayChanguService')) {
        throw new Exception("PayChanguService unavailable");
    }

    $payChangu = new PayChanguService();
    $paymentData = [
        'amount' => $amount,
        'currency' => 'MWK',
        'email' => $user['email'] ?? 'customer@kweza.com',
        'first_name' => $user['first_name'] ?? 'Kweza',
        'last_name' => $user['last_name'] ?? 'User',
        'callback_url' => $callbackUrl,
        'return_url' => $returnUrl,
        'tx_ref' => $tx_ref,
        'meta' => [
            'event_id' => $event_id,
            'user_id' => $user['user_id'],
            'type' => 'ticket_purchase'
        ],
        'customization' => [
            'title' => 'Ticket: ' . $event['event_name'],
            'description' => 'Event Ticket Purchase'
        ]
    ];

    $response = $payChangu->initiatePayment($paymentData);

    if (isset($response['status']) && $response['status'] === 'success') {
        echo json_encode(['success' => true, 'checkout_url' => $response['data']['checkout_url']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Payment initiation failed', 'details' => $response]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
