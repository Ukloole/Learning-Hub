<?php
/**
 * UKLOOLE — Save Community Member After Payment
 * Called from community.html after Paystack success callback
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once 'db.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$name      = htmlspecialchars(trim($input['name']      ?? ''));
$email     = filter_var(trim($input['email']    ?? ''), FILTER_VALIDATE_EMAIL);
$reference = htmlspecialchars(trim($input['reference'] ?? ''));
$amount    = floatval($input['amount'] ?? 25000);

if (!$name || !$email || !$reference) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}

// Verify Paystack reference
$verify_url = 'https://api.paystack.co/transaction/verify/' . urlencode($reference);
$ch = curl_init($verify_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . 'sk_live_REPLACE_WITH_YOUR_SECRET_KEY',
        'Content-Type: application/json',
    ],
]);
$response = curl_exec($ch);
$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$pay_data = json_decode($response, true);
$verified = $code === 200 && isset($pay_data['data']['status']) && $pay_data['data']['status'] === 'success';

if (!$verified) {
    http_response_code(402);
    echo json_encode(['ok' => false, 'error' => 'Payment could not be verified']);
    exit;
}

// Upsert member
$stmt = $conn->prepare(
    "INSERT INTO community_members (name, email, reference, amount, status)
     VALUES (?, ?, ?, ?, 'active')
     ON DUPLICATE KEY UPDATE reference=VALUES(reference), amount=VALUES(amount), status='active'"
);
$stmt->bind_param("sssd", $name, $email, $reference, $amount);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database error']);
    exit;
}
$stmt->close();
$conn->close();

echo json_encode(['ok' => true, 'message' => 'Member saved. Redirecting to login.']);
